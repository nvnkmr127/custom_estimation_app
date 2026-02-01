<?php

namespace App\Jobs;

use App\Models\WebhookConfig;
use App\Models\WebhookDelivery;
use App\Models\WebhookEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class WebhookDeliveryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $backoff = [10, 60, 300, 1800, 3600];

    public function __construct(
        public WebhookConfig $webhookConfig,
        public WebhookEvent $webhookEvent
    ) {
    }

    public function handle(): void
    {
        $startTime = microtime(true);
        $attempt = $this->attempts();

        // 0. Create Delivery Record Early (to track failures like validation)
        $delivery = WebhookDelivery::create([
            'webhook_id' => $this->webhookConfig->id,
            'webhook_event_id' => $this->webhookEvent->id,
            'status' => 'processing',
            'attempt' => $attempt,
            'request_headers' => null, // Will update later
        ]);

        try {
            // 1. Prepare Payload & Headers
            $rawPayload = $this->webhookEvent->payload;

            // Apply Mapping if configured
            if (!empty($this->webhookConfig->payload_mapping)) {
                // We usually map from the 'normalized' part of the payload, or the root?
                // The envelope has 'payload.normalized'.
                // Let's pass the ENTIRE envelope as source, so users can map metadata too.
                $mapper = app(\App\Webhooks\PayloadMapper::class);
                $payload = $mapper->map($rawPayload, $this->webhookConfig->payload_mapping);
            } else {
                $payload = $rawPayload;
            }

            // Validate Payload if configured
            if (!empty($this->webhookConfig->validation_schema)) {
                $validator = app(\App\Webhooks\PayloadValidator::class);
                // Verify this throws ValidationException on failure
                $validator->validate($payload, $this->webhookConfig->validation_schema);
            }

            $jsonPayload = json_encode($payload);
            $method = $this->webhookConfig->http_method ?? 'POST';

            $headers = array_merge($this->webhookConfig->headers ?? [], [
                'Content-Type' => 'application/json',
                'User-Agent' => 'Laravel-Webhook-System/1.0',
                'X-Webhook-ID' => $this->webhookEvent->id,
                'X-Delivery-Attempt' => (string) $attempt,
            ]);

            if ($this->webhookConfig->secret) {
                $timestamp = time();
                $headers['X-Webhook-Timestamp'] = (string) $timestamp;

                // Sign the timestamp and payload to prevent replay attacks
                $toSign = "{$timestamp}.{$jsonPayload}";
                $signature = hash_hmac('sha256', $toSign, $this->webhookConfig->secret);

                $headers['X-Webhook-Signature'] = $signature;
            }

            // Update Delivery with Headers
            $delivery->update(['request_headers' => $headers]);

            // 3. Send Request
            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->withBody($jsonPayload, 'application/json')
                ->send($method, $this->webhookConfig->url);

            $responseStatus = $response->status();

            // 4. Handle Failure (4xx/5xx)
            if ($response->failed()) {
                throw new \Exception("HTTP {$responseStatus}");
            }

            // 5. Success
            $this->updateDelivery($delivery, 'success', $startTime, $response);

        } catch (\Exception $e) {
            $shouldRetry = $this->handleFailure($delivery, $e, $startTime, $response ?? null);
            if ($shouldRetry) {
                throw $e; // Trigger Laravel Retry
            }
            // If strictly failed (e.g. 4xx or max attempts handled in handleFailure implicitly checks attempts), we suppress exception to stop queue retry.
            // NOTE: $this->attempts() is current attempt.
            // If we suppress exception, the job is marked "Done". Ideally we mark it "Failed" in Queue too via fail()?
            // $this->fail($e) marks as failed_jobs table entry.
            // Let's call $this->fail($e).
            $this->fail($e);
        }
    }

    protected function updateDelivery(WebhookDelivery $delivery, string $status, float $startTime, $response = null, ?string $error = null): void
    {
        $duration = (int) ((microtime(true) - $startTime) * 1000);

        $data = [
            'status' => $status,
            'duration_ms' => $duration,
            'response_status' => $response?->status(),
            'response_body' => $response?->body(),
            'response_headers' => $response ? $response->headers() : null,
            'error_message' => $error,
        ];

        if ($status === 'retrying') {
            // Calculate next retry based on backoff
            $attempt = $this->attempts();
            $inSeconds = $this->backoff[$attempt - 1] ?? 60;
            $data['next_retry_at'] = now()->addSeconds($inSeconds);

            // Metric: Retry
            app(\App\Webhooks\WebhookMetricsCollector::class)->increment($this->webhookConfig->id, 'retry');
        }

        $delivery->update($data);

        // Structured Logging
        app(\App\Webhooks\WebhookLogger::class)->logAttempt($delivery, $status, $data, $this->webhookEvent);

        // Metric: Success/Failure + Latency
        $metricType = match ($status) {
            'success' => 'success',
            'failed' => 'failure',
            default => null
        };

        if ($metricType) {
            app(\App\Webhooks\WebhookMetricsCollector::class)->increment(
                $this->webhookConfig->id,
                $metricType,
                $data['duration_ms'] ?? 0
            );
        }
    }

    protected function handleFailure(WebhookDelivery $delivery, \Exception $e, float $startTime, $response = null): bool
    {
        $willRetry = $this->attempts() < $this->tries;
        $responseStatus = $response?->status();

        // Permanent Failures (4xx) - Don't retry
        // But 429 (Too Many Requests) shoud probably retry (backoff).
        // 408 (Timeout) retry.
        // 401/403/404/400/422 -> Likely config/resource issue.
        if ($responseStatus && $responseStatus >= 400 && $responseStatus < 500 && $responseStatus !== 429 && $responseStatus !== 408) {
            $willRetry = false;
        }

        // Validation Errors are permanent
        if ($e instanceof \Illuminate\Validation\ValidationException) {
            $willRetry = false;
        }

        $status = $willRetry ? 'retrying' : 'failed';
        $errorMsg = $e->getMessage();

        $this->updateDelivery($delivery, $status, $startTime, $response, $errorMsg);

        if (!$willRetry) {
            // DLQ Logic
            // DLQ Logic
            \App\Models\WebhookDeadLetter::create([
                'original_delivery_id' => $delivery->id,
                'final_error_message' => "{$status}: {$errorMsg}",
                'snapshot_payload' => $this->webhookEvent->payload, // Snapshot current payload
            ]);

            // Metric: DLQ
            app(\App\Webhooks\WebhookMetricsCollector::class)->increment($this->webhookConfig->id, 'dlq');

            // If we decided not to retry due to 4xx, we must ensure the job doesn't retry automatically
            // by NOT throwing the exception or using fail().
            // However, typical Laravel Job retry logic is triggered by thrown Exception.
            // If we don't throw, the job is "finished" (success status in queue).
            // But we marked our Delivery as "failed".
            // To properly stop Queue retry, we can use $this->fail($e) or just return.
            // But if we throw, Laravel retries until maxAttempts.
            // If we manually set 'failed' and want to STOP retrying, we should capture the exception and NOT rethrow if !willRetry.
        } else {
            // logic moved...
        }

        return $willRetry;
    }
}

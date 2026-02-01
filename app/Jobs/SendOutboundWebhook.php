<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendOutboundWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 5;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [10, 60, 300, 1800, 3600];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public \App\Models\WebhookConfig $webhookConfig,
        public \App\Models\WebhookEvent $webhookEvent
    ) {
    }

    public function handle(): void
    {
        $startTime = microtime(true);
        $attempt = $this->attempts();

        // 1. Prepare Headers & Payload
        $payload = $this->webhookEvent->payload;
        $jsonPayload = json_encode($payload);

        $headers = array_merge($this->webhookConfig->headers ?? [], [
            'Content-Type' => 'application/json',
            'User-Agent' => 'Laravel-Webhook-System/1.0',
            'X-Webhook-ID' => $this->webhookEvent->id,
            'X-Delivery-Attempt' => (string) $attempt,
        ]);

        if ($this->webhookConfig->secret) {
            $headers['X-Webhook-Signature'] = hash_hmac('sha256', $jsonPayload, $this->webhookConfig->secret);
        }

        // 2. Create Delivery Record (Pending)
        $delivery = \App\Models\WebhookDelivery::create([
            'webhook_id' => $this->webhookConfig->id,
            'webhook_event_id' => $this->webhookEvent->id,
            'status' => 'processing',
            'attempt' => $attempt,
        ]);

        $response = null;
        $responseStatus = 0;
        $errorMessage = null;

        try {
            // 3. Send Request
            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->withBody($jsonPayload, 'application/json')
                ->post($this->webhookConfig->url);

            $responseStatus = $response->status();

            if ($response->failed()) {
                throw new \Exception("Webhook delivery failed with status {$responseStatus}");
            }

            // Success
            $duration = (int) ((microtime(true) - $startTime) * 1000);
            $delivery->update([
                'status' => 'success',
                'response_status' => $responseStatus,
                'response_body' => $response->body(),
                'duration_ms' => $duration,
            ]);

        } catch (\Exception $e) {
            $duration = (int) ((microtime(true) - $startTime) * 1000);
            $errorMessage = $e->getMessage();

            // 5. Update Delivery (Failed/Retrying)
            $willRetry = !($this->job?->hasFailed()) && $attempt < $this->tries;

            $delivery->update([
                'status' => $willRetry ? 'retrying' : 'failed',
                'response_status' => $responseStatus, // Holds 0 (network) or 500 (http)
                'response_body' => $response?->body(),
                'error_message' => $errorMessage,
                'duration_ms' => $duration,
                'next_retry_at' => $willRetry ? now()->addSeconds($this->backoff[$attempt - 1] ?? 60) : null
            ]);

            throw $e;
        }
    }
}

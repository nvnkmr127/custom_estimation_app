<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Core\Events\DomainEvent;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the queued listener may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    /**
     * The number of seconds to wait before retrying the queued listener.
     *
     * @var array
     */
    public $backoff = [10, 60, 180, 600, 3600];

    protected $decisionService;

    public function __construct(
        \App\Services\Notifications\NotificationDecisionService $decisionService
    ) {
        $this->decisionService = $decisionService;
    }

    /**
     * Handle the event.
     */
    public function handle(DomainEvent $event): void
    {
        $decision = $this->decisionService->evaluate($event);

        if (!$decision->shouldNotify || !in_array('webhook', $decision->channels)) {
            return;
        }

        $eventName = $event->getEventName();
        $webhookUrl = null;

        if ($eventName === 'estimate.submitted_for_approval') {
            $webhookUrl = Setting::getCached('estimate_approval_webhook_url');
        } elseif ($eventName === 'estimate.sent') {
            $webhookUrl = Setting::getCached('estimate_client_webhook_url');
        }

        if ($webhookUrl) {
            $payload = [
                'event' => $eventName,
                'timestamp' => now()->toIso8601String(),
                'payload' => $event->getPayload(),
                'metadata' => [
                    'event_id' => $event->getEventId(),
                    'entity_type' => $event->getEntityType(),
                    'entity_id' => $event->getEntityId(),
                    'triggered_by' => $event->getTriggeredBy(),
                    'urgency' => $decision->urgency,
                ]
            ];

            $jsonPayload = json_encode($payload);
            $headers = ['Content-Type' => 'application/json'];

            // 1. Optional HMAC Signature
            $secret = Setting::getCached('webhook_hmac_secret');
            if ($secret) {
                $headers['X-Webhook-Signature'] = hash_hmac('sha256', $jsonPayload, $secret);
            }

            $startTime = microtime(true);
            $attempt = $this->attempts();

            try {
                $response = Http::timeout(10)
                    ->withHeaders($headers)
                    ->withBody($jsonPayload, 'application/json')
                    ->post($webhookUrl);

                $latency = (microtime(true) - $startTime) * 1000;

                // 2. Log to Database
                \Illuminate\Support\Facades\DB::table('webhook_logs')->insert([
                    'event_name' => $eventName,
                    'webhook_url' => $webhookUrl,
                    'event_id' => $event->getEventId(),
                    'payload' => $jsonPayload,
                    'status_code' => $response->status(),
                    'response_body' => mb_substr($response->body(), 0, 1000), // Cap size
                    'latency_ms' => $latency,
                    'attempt' => $attempt,
                    'status' => $response->successful() ? 'success' : ($attempt < $this->tries ? 'retrying' : 'failed'),
                    'error_message' => $response->successful() ? null : "Status: {$response->status()}",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($response->failed()) {
                    Log::error("Webhook delivery failed for event {$eventName} to {$webhookUrl}", [
                        'status' => $response->status(),
                    ]);
                    $this->fail(new \Exception("Webhook responded with status {$response->status()}"));
                }
            } catch (\Exception $e) {
                $latency = (microtime(true) - $startTime) * 1000;

                \Illuminate\Support\Facades\DB::table('webhook_logs')->insert([
                    'event_name' => $eventName,
                    'webhook_url' => $webhookUrl,
                    'event_id' => $event->getEventId(),
                    'payload' => $jsonPayload,
                    'status_code' => 0,
                    'latency_ms' => $latency,
                    'attempt' => $attempt,
                    'status' => $attempt < $this->tries ? 'retrying' : 'failed',
                    'error_message' => mb_substr($e->getMessage(), 0, 500),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::error("Error sending webhook for event {$eventName}: " . $e->getMessage());
                throw $e;
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(DomainEvent $event, \Throwable $exception): void
    {
        Log::error('WebhookListener Failed: ' . $exception->getMessage(), [
            'event_id' => $event->getEventId(),
            'event_name' => $event->getEventName(),
        ]);
    }
}

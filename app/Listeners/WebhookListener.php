<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Core\Events\DomainEvent;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

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
        \Illuminate\Support\Facades\Log::info("WebhookListener: Handling event " . $event->getEventName());
        $actorId = $event->getTriggeredBy();
        $actor = $actorId ? \App\Models\User::find($actorId) : null;
        $decision = $this->decisionService->evaluate($event, null, $actor);

        if (!$decision->shouldNotify) {
            Log::debug("WebhookListener: Skipping because decision says NOT to notify.", ['event' => $event->getEventName()]);
            return;
        }

        if (!in_array('webhook', $decision->channels)) {
            Log::debug("WebhookListener: Skipping because 'webhook' is not in active channels.", [
                'event' => $event->getEventName(),
                'active_channels' => $decision->channels
            ]);
            return;
        }

        // Record for deduplication (since this is an global/system channel, we use 'system' as identifier)
        $cacheKey = "notify_dedup:" . md5($event->getEventName() . ":" . $event->getEntityType() . ":" . $event->getEntityId() . ":system");
        $cooldownMinutes = Setting::getCached("notification_cooldown_" . $event->getEventName(), 5);
        Cache::put($cacheKey, true, now()->addMinutes($cooldownMinutes));

        $eventName = $event->getEventName();
        $webhookUrl = null;

        if ($eventName === 'estimate.submitted_for_approval' || $eventName === 'estimate.approved' || $eventName === 'estimate.accepted') {
            $webhookUrl = Setting::getCached('estimate_approval_webhook_url');
        } elseif ($eventName === 'estimate.sent' || $eventName === 'estimate.created') {
            $webhookUrl = Setting::getCached('estimate_client_webhook_url');
        }

        if (!$webhookUrl) {
            Log::warning("WebhookListener: No URL configured for event {$eventName}.", [
                'possible_keys' => ['estimate_approval_webhook_url', 'estimate_client_webhook_url']
            ]);
            return;
        }
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

        // 0. Inject Estimate Links if applicable
        if ($event->getEntityType() === 'estimate') {
            $estimate = \App\Models\Estimate::find($event->getEntityId());
            if ($estimate) {
                $payload['links'] = [
                    'online_view_url' => $estimate->public_url,
                    'pdf_download_url' => route('estimates.pdf', $estimate->id),
                ];
                // Also add to main payload for easier access
                $payload['online_view_url'] = $estimate->public_url;
                $payload['pdf_download_url'] = route('estimates.pdf', $estimate->id);
            }
        }

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

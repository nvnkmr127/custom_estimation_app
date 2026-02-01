<?php

namespace App\Webhooks;

use App\Core\Events\DomainEvent;
use App\Models\WebhookConfig;
use App\Models\WebhookEvent;
use App\Jobs\SendOutboundWebhook;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class WebhookEventDispatcher
{
    public function __construct(
        protected WebhookEventRegistry $registry
    ) {
    }

    /**
     * Dispatch a domain event to subscribed webhooks.
     * 
     * @param DomainEvent|object $event
     * @param string|null $idempotencyKey Optional override
     */
    public function dispatch(object $event, ?string $idempotencyKey = null): void
    {
        // 1. Resolve Event Name & Resource
        $eventName = $this->resolveEventName($event);
        if (!$eventName) {
            return;
        }

        // 2. Build Payload (via Registry or fallback)
        $payloadData = $this->resolvePayload($eventName, $event);
        $occurredAt = method_exists($event, 'getOccurredOn') ? $event->getOccurredOn() : now();
        $source = 'system.internal'; // Default source

        // 3. Resolve Idempotency Key
        $key = $idempotencyKey ?? $this->resolveIdempotencyKey($event);

        // 4. Construct Envelope
        $envelope = [
            'event_id' => method_exists($event, 'getEventId') ? $event->getEventId() : Str::uuid()->toString(),
            'event_type' => $eventName,
            'source' => $source,
            'occurred_at' => $occurredAt->format(\DateTimeInterface::ATOM),
            'version' => '1.0',
            'payload' => [
                'normalized' => $payloadData,
                'raw' => null, // Could be original event data if needed
            ],
            'metadata' => [
                'trace_id' => Str::uuid()->toString(),
                'attempt' => 1,
            ]
        ];

        // Add metadata from event if available
        if (method_exists($event, 'getEntityType')) {
            $envelope['metadata']['entity_type'] = $event->getEntityType();
            $envelope['metadata']['entity_id'] = $event->getEntityId();
        }

        // 5. Persist to WebhookEvent (Idempotency Check)
        $webhookEvent = WebhookEvent::firstOrCreate(
            ['idempotency_key' => $key],
            [
                'event_type' => $eventName,
                'payload' => $envelope,
                'occurred_at' => $occurredAt,
            ]
        );

        if (!$webhookEvent->wasRecentlyCreated) {
            Log::info("WebhookEventDispatcher: Idempotent skip for key {$key}");
            // We return here to strictly enforce idempotency (don't re-dispatch jobs).
            // However, if previous delivery failed, maybe we *should* re-dispatch?
            // "Ensure idempotency support" usually means "processing the same message twice has same effect as once".
            // If the first one is processing, we stop.
            return;
        }

        // 6. Resolve Subscribers
        $subscribers = $this->resolveSubscribers($eventName);

        Log::info("WebhookEventDispatcher: Dispatched {$eventName} to " . $subscribers->count() . " subscribers.");

        // 7. Enqueue Jobs
        foreach ($subscribers as $config) {
            $job = \App\Jobs\WebhookDeliveryJob::dispatch($config, $webhookEvent)
                ->onQueue('webhooks');

            if ($config->delay > 0) {
                $job->delay($config->delay);
            }
        }
    }

    protected function resolveEventName(object $event): ?string
    {
        if ($event instanceof DomainEvent) {
            return $event->getEventName();
        }
        // Fallback or other interfaces
        return null;
    }

    protected function resolvePayload(string $eventName, object $event): array
    {
        // Try Registry
        $def = $this->registry->get($eventName);
        if ($def) {
            // Need the actual resource object. DomainEvent usually has it or ID.
            // If DomainEvent doesn't expose the object, we can't use the builder easily unless we fetch it.
            // For now, fallback to DomainEvent payload if available.
            // Or try to see if $event ITSELF is the resource (unlikely for DomainEvent wrapper).
        }

        if ($event instanceof DomainEvent) {
            return $event->getPayload();
        }

        return [];
    }

    protected function resolveIdempotencyKey(object $event): string
    {
        if (method_exists($event, 'getEventId')) {
            return $event->getEventId();
        }
        return Str::uuid()->toString();
    }

    protected function resolveSubscribers(string $eventName): \Illuminate\Database\Eloquent\Collection
    {
        // This logic mimics the listener's matching but optimized
        return WebhookConfig::where('status', 'active')
            ->get()
            ->filter(function ($config) use ($eventName) {
                if (empty($config->events))
                    return false;
                foreach ($config->events as $pattern) {
                    if (fnmatch($pattern, $eventName))
                        return true;
                }
                return false;
            });
    }

    /**
     * Dispatch an existing (already persisted) WebhookEvent model.
     */
    public function dispatchExisting(WebhookEvent $webhookEvent): void
    {
        $eventName = $webhookEvent->event_type;
        $subscribers = $this->resolveSubscribers($eventName);

        Log::info("WebhookEventDispatcher: Replaying {$eventName} ({$webhookEvent->id}) to " . $subscribers->count() . " subscribers.");

        foreach ($subscribers as $config) {
            $job = \App\Jobs\WebhookDeliveryJob::dispatch($config, $webhookEvent)
                ->onQueue('webhooks');

            if ($config->delay > 0) {
                $job->delay($config->delay);
            }
        }
    }
}

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
            'id' => method_exists($event, 'getEventId') ? $event->getEventId() : Str::uuid()->toString(),
            'event' => $eventName,
            'timestamp' => $occurredAt->format(\DateTimeInterface::ATOM),
            'data' => $payloadData,
            'metadata' => [
                'source' => $source,
                'version' => '1.0',
                'trace_id' => Str::uuid()->toString(),
                'attempt' => 1,
            ]
        ];

        // Add metadata from event if available
        if (method_exists($event, 'getEntityType')) {
            $envelope['metadata']['entity_type'] = $event->getEntityType();
            $envelope['metadata']['entity_id'] = $event->getEntityId();
        }
        if (method_exists($event, 'getTriggeredBy')) {
            $envelope['metadata']['triggered_by'] = $event->getTriggeredBy();
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

        if (method_exists($event, 'getEventName')) {
            return $event->getEventName();
        }

        return null;
    }

    protected function resolvePayload(string $eventName, object $event): array
    {
        // 1. Try Registry Definition
        $def = $this->registry->get($eventName);
        if ($def) {
            $resource = $this->findResourceInEvent($event, $def->resourceClass());
            if ($resource) {
                try {
                    return $def->buildPayload($resource);
                } catch (\Exception $e) {
                    Log::error("WebhookEventDispatcher: Failed to build payload via definition for {$eventName}: " . $e->getMessage());
                }
            }
        }

        // 2. Fallback to Event's native payload
        if ($event instanceof DomainEvent) {
            return $event->getPayload();
        }

        if (method_exists($event, 'getPayload')) {
            return $event->getPayload();
        }

        return [];
    }

    protected function findResourceInEvent(object $event, string $resourceClass): ?object
    {
        // Try common property names
        $props = ['resource', 'model', 'data', 'object'];

        // Add resource name as property (e.g. 'estimate' for Estimate::class)
        try {
            $shortName = strtolower((new \ReflectionClass($resourceClass))->getShortName());
            $props[] = $shortName;
        } catch (\Exception $e) {
        }

        foreach ($props as $prop) {
            if (isset($event->{$prop}) && $event->{$prop} instanceof $resourceClass) {
                return $event->{$prop};
            }
        }

        // Try method names
        if (isset($shortName)) {
            $methods = ['get' . ucfirst($shortName), 'getResource', 'getModel'];
            foreach ($methods as $method) {
                if (method_exists($event, $method)) {
                    $val = $event->{$method}();
                    if ($val instanceof $resourceClass) {
                        return $val;
                    }
                }
            }
        }

        return null;
    }

    protected function resolveIdempotencyKey(object $event): string
    {
        if (method_exists($event, 'getEventId')) {
            return $event->getEventId();
        }
        return Str::uuid()->toString();
    }

    /**
     * Resolve subscribers for an event.
     */
    public function resolveSubscribers(string $eventName, bool $includeInactive = false): \Illuminate\Database\Eloquent\Collection
    {
        $query = WebhookConfig::query();
        if (!$includeInactive) {
            $query->where('status', 'active');
        }

        return $query->get()
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
    public function dispatchExisting(WebhookEvent $webhookEvent, bool $includeInactive = false): int
    {
        $eventName = $webhookEvent->event_type;
        $subscribers = $this->resolveSubscribers($eventName, $includeInactive);

        Log::info("WebhookEventDispatcher: Replaying {$eventName} ({$webhookEvent->id}) to " . $subscribers->count() . " subscribers.");

        $count = 0;
        foreach ($subscribers as $config) {
            $job = \App\Jobs\WebhookDeliveryJob::dispatch($config, $webhookEvent)
                ->onQueue('webhooks');

            if ($config->delay > 0) {
                $job->delay($config->delay);
            }
            $count++;
        }

        return $count;
    }
}

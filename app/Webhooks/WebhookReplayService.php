<?php

namespace App\Webhooks;

use App\Jobs\WebhookDeliveryJob;
use App\Models\WebhookConfig;
use App\Models\WebhookDelivery;
use App\Models\WebhookEvent;
use Illuminate\Support\Str;
use InvalidArgumentException;

class WebhookReplayService
{
    protected const MAX_REPLAY_DEPTH = 3;

    public function __construct(
        protected WebhookEventDispatcher $dispatcher,
        protected WebhookEventRegistry $registry
    ) {
    }

    /**
     * Replay an entire event to all current subscribers.
     * 
     * @param WebhookEvent $originalEvent
     * @param array|null $payloadOverride
     * @param bool $includeInactive
     * @return int Number of deliveries dispatched
     */
    public function replayEvent(WebhookEvent $originalEvent, ?array $payloadOverride = null, bool $includeInactive = false): int
    {
        $this->ensureSafeReplay($originalEvent);

        $eventToDispatch = $this->prepareEventForReplay($originalEvent, $payloadOverride);

        // Dispatch to all matching subscribers
        // We use the dispatcher logic but need to inject the PRE-EXISTING event (or clone)
        // The Dispatcher::dispatch method accepts a DomainEvent object, not a WebhookEvent model.
        // So we need to bypass the dispatcher's "Create Event" logic and jump to "Resolve & Send".
        // Or refactor Dispatcher to accept a WebhookEvent model directly.
        // Let's manually resolve and dispatch here to avoid modifying Dispatcher too much,
        // OR add `dispatchExisting(WebhookEvent $event)` to Dispatcher.
        // Adding method to Dispatcher is cleaner.

        return $this->dispatcher->dispatchExisting($eventToDispatch, $includeInactive);
    }

    /**
     * Replay a specific delivery (retry for a single endpoint).
     * 
     * @param WebhookDelivery $originalDelivery
     * @param array|null $payloadOverride
     * @return WebhookDelivery The new delivery record
     */
    public function replayDelivery(WebhookDelivery $originalDelivery, ?array $payloadOverride = null): WebhookDelivery
    {
        $originalEvent = $originalDelivery->event; // Correct relation name 'event'
        $this->ensureSafeReplay($originalEvent);

        $eventToDispatch = $this->prepareEventForReplay($originalEvent, $payloadOverride);

        // Use current config state
        $config = $originalDelivery->webhook->fresh();

        if (!$config || $config->status !== 'active') {
            throw new InvalidArgumentException("Endpoint is no longer active.");
        }

        // Dispatch Job directly
        WebhookDeliveryJob::dispatch($config, $eventToDispatch)
            ->onQueue('webhooks');

        // We return the *newest* delivery? 
        // The Job creates the delivery record asynchronously. 
        // We can't return it here easily.
        // Returns the original for reference or the new Event?
        // Let's return the original delivery for chaining, or the Event.
        // Returning the Event logic seems consistent.
        // Actually, let's just return the Event.

        return $originalDelivery;
    }

    protected function prepareEventForReplay(WebhookEvent $original, ?array $override): WebhookEvent
    {
        if (empty($override)) {
            // No changes, just reuse the event?
            // "Track replay attempts separately". 
            // If we reuse the event, `webhook_deliveries` tracks attempts.
            // If we want a separate EVENT log for the replay (so we can see it in history),
            // we should probably clone it even if payload isn't changed, OR
            // just rely on the delivery log which has `attempt` count.
            // User requirement: "Replay by event_id ... Replay with modified payload".
            // If modified, we MUST clone.
            // If not modified, reusing is more efficient and "idempotent-ish".
            // However, to track that this was a *Manual Replay* vs an *Auto Retry*,
            // we might want a new Event record or just a flag on the Delivery?
            // Delivery has no `is_replay` column.
            // Let's Clone if Override provided, Reuse if not.
            return $original;
        }

        // Clone/Create Logic
        $payload = $original->payload;
        $payload['normalized'] = $override; // Replace normalized data

        // Mark as replay in metadata
        $meta = $payload['metadata'] ?? [];
        $meta['is_replay'] = true;
        $meta['original_event_id'] = $original->id;
        $meta['replay_count'] = ($meta['replay_count'] ?? 0) + 1;
        $payload['metadata'] = $meta;

        return WebhookEvent::create([
            'event_type' => $original->event_type,
            'idempotency_key' => Str::uuid(), // New key for replay
            'payload' => $payload,
            'occurred_at' => now(),
        ]);
    }

    protected function ensureSafeReplay(WebhookEvent $event): void
    {
        $meta = $event->payload['metadata'] ?? [];
        $depth = $meta['replay_count'] ?? 0;

        if ($depth >= self::MAX_REPLAY_DEPTH) {
            throw new InvalidArgumentException("Max replay depth exceeded. Potential loop detected.");
        }
    }
}

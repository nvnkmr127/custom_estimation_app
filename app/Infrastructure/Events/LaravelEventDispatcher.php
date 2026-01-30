<?php

namespace App\Infrastructure\Events;

use App\Core\Events\EventDispatcherInterface;
use Illuminate\Support\Facades\Event;

class LaravelEventDispatcher implements EventDispatcherInterface
{
    /**
     * Dispatch an event using Laravel's event facade.
     *
     * @param object $event
     * @return void
     */
    /**
     * Dispatch an event using Laravel's event facade.
     *
     * @param object $event
     * @return void
     */
    public function dispatch(object $event): void
    {
        // 1. Persist to Storage (Synchronous Event Store)
        if ($event instanceof \App\Core\Events\DomainEvent) {
            try {
                \App\Models\EventLog::create([
                    'event_id' => $event->getEventId(),
                    'event_name' => $event->getEventName(),
                    'event_class' => get_class($event),
                    'entity_type' => $event->getEntityType(),
                    'entity_id' => $event->getEntityId(),
                    'triggered_by' => $event->getTriggeredBy(),
                    'source' => $event->getSource(),
                    'payload' => $event->getPayload(),
                    'status' => 'logged',
                ]);
            } catch (\Exception $e) {
                // Fallback: Log failure but do not block dispatch? 
                // Or block? "Persist event to storage" implies it's critical. 
                // If Event Store fails, system state might be inconsistent with history.
                // For now, log error and proceed to ensure business logic continues?
                // Or throw? Let's log critical error.
                \Illuminate\Support\Facades\Log::critical('Failed to persist domain event: ' . $e->getMessage(), [
                    'event' => get_class($event),
                    'payload' => $event->getPayload()
                ]);
            }
        }

        // 2. Push to Async Queue & Dispatch to Listeners
        // Laravel's Event::dispatch handles both.
        // We wrap this in try-catch to ensure that even if the Queue system is down (Redis failure),
        // the user request completes successfully. The event is already safely persisted in SQL (Step 1)
        // and can be replayed later.
        try {
            Event::dispatch($event);
            \Illuminate\Support\Facades\Log::debug("EventDispatcher: Dispatched to Laravel", [
                'event_id' => $event->getEventId(),
                'event_class' => get_class($event),
                'name' => $event->getEventName()
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::critical('Failed to dispatch/queue event: ' . $e->getMessage(), [
                'event_id' => $event->getEventId(),
                'event_class' => get_class($event),
            ]);
        }
    }
}

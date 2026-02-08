<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Core\Events\DomainEvent;
use Illuminate\Support\Facades\Log;

class AuditListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(DomainEvent $event): void
    {
        // Basic file logging for audit trail redundancy
        Log::channel('daily')->info('AUDIT: ' . $event->getEventName(), [
            'event_id' => $event->getEventId(),
            'entity' => $event->getEntityType() . ':' . $event->getEntityId(),
            'triggered_by' => $event->getTriggeredBy(),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(DomainEvent $event, \Throwable $exception): void
    {
        Log::channel('daily')->error('AUDIT LISTENER FAILED: ' . $event->getEventName(), [
            'event_id' => $event->getEventId(),
            'error' => $exception->getMessage(),
        ]);
    }
}

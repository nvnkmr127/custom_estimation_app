<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Core\Events\DomainEvent;

class WebhookListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the queued listener may be attempted.
     *
     * @var int
     */
    public $tries = 5; // Webhooks might need more retries

    /**
     * The number of seconds to wait before retrying the queued listener.
     *
     * @var array
     */
    public $backoff = [10, 60, 180, 600, 3600]; // Exponential backoff

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
        // Placeholder: Logic to fire webhooks will go here eventually.
    }

    /**
     * Handle a job failure.
     */
    public function failed(DomainEvent $event, \Throwable $exception): void
    {
        \Illuminate\Support\Facades\Log::error('WebhookListener Failed: ' . $exception->getMessage(), [
            'event_id' => $event->getEventId(),
        ]);
    }
}

<?php

namespace App\Jobs;

use App\Core\Events\DomainEvent;
use App\Models\EventLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LogEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public DomainEvent $event
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        EventLog::create([
            'event_id' => $this->event->getEventId(),
            'event_name' => $this->event->getEventName(),
            'event_class' => get_class($this->event),
            'entity_type' => $this->event->getEntityType(),
            'entity_id' => $this->event->getEntityId(),
            'triggered_by' => $this->event->getTriggeredBy(),
            'source' => $this->event->getSource(),
            'payload' => $this->event->getPayload(),
            'status' => 'logged',
        ]);
    }
}

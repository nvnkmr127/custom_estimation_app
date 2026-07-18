<?php

namespace App\Listeners;

use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Log;
use Illuminate\Events\Dispatcher;
use App\Models\EventListenerLog;
use App\Models\EventLog;

class QueueLifecycleSubscriber
{
    /**
     * Subscribe to queue events.
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            JobProcessing::class => 'handleJobProcessing',
            JobProcessed::class => 'handleJobProcessed',
            JobFailed::class => 'handleJobFailed',
        ];
    }

    public function handleJobProcessing(JobProcessing $event): void
    {
        $payload = $event->job->payload();
        $this->logListenerStatus($payload, 'processing');
    }

    public function handleJobProcessed(JobProcessed $event): void
    {
        $payload = $event->job->payload();
        $this->logListenerStatus($payload, 'success');
    }

    public function handleJobFailed(JobFailed $event): void
    {
        $payload = $event->job->payload();
        $this->logListenerStatus($payload, 'failed', $event->exception);
    }

    private function logListenerStatus(array $payload, string $status, ?\Throwable $exception = null): void
    {
        // We only care about jobs that are Event Listeners
        // Laravel wraps listeners in CallQueuedHandler
        if (!isset($payload['displayName']) || strpos($payload['displayName'], 'CallQueuedHandler') === false) {
            return;
        }

        // Extract the Listener Class and the Event
        // The payload 'data' usually contains 'command' which is a serialized CallQueuedListener object.
        // Unserializing it is risky/heavy.
        // Instead, we can try to inspect the payload structure 'data' -> 'commandName' or similar.
        // But CallQueuedListener stores the actual class in properties.

        // Simpler approach for Phase 1: Use regex on the serialized command string if available
        // OR rely on 'displayName' if it contains the Listener class.
        // 'displayName' is usually "App\Listeners\MyListener"

        $listenerClass = $payload['displayName'] ?? 'UnknownListener';

        // We need the Event ID (UUID) to link it!
        // The Event Object is inside the serialized command.
        // This is the tricky part of generic logging. 
        // We need to unserialize the command to get the event.

        try {
            $command = unserialize($payload['data']['command']);

            // Corrupt/incompatible payloads unserialize to false — bail cleanly instead
            // of tripping property_exists() on a non-object.
            if (!is_object($command)) {
                return;
            }

            // Case 1: Standard Laravel Listener
            if (property_exists($command, 'data') && !empty($command->data)) {
                $domainEvent = $command->data[0] ?? null;
            }
            // Case 2: Some custom dispatchers might store event differently
            elseif (property_exists($command, 'event')) {
                $domainEvent = $command->event;
            } else {
                return;
            }

            if ($domainEvent instanceof \App\Core\Events\DomainEvent) {
                $eventId = $domainEvent->getEventId();

                EventListenerLog::updateOrCreate(
                    [
                        'event_id' => $eventId,
                        'listener_class' => $listenerClass,
                    ],
                    [
                        'status' => $status,
                        'attempts' => $payload['attempts'] ?? 1,
                        'error_message' => $exception ? $exception->getMessage() : null,
                    ]
                );
            }

        } catch (\Throwable $e) {
            // Log warning but don't crash queue
            Log::warning("QueueLifecycleSubscriber: Failed to parse listener job.", [
                'error' => $e->getMessage(),
                'listener' => $listenerClass
            ]);
        }
    }
}

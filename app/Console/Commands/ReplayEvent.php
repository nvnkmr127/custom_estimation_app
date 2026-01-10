<?php

namespace App\Console\Commands;

use App\Models\EventLog;
use App\Core\Events\EventDispatcherInterface;
use Illuminate\Console\Command;

class ReplayEvent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'event:replay {uuid : The UUID of the event to replay}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Replay an event from the event log by its UUID';

    public function __construct(
        protected EventDispatcherInterface $dispatcher
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $uuid = $this->argument('uuid');
        $log = EventLog::where('event_id', $uuid)->first();

        if (!$log) {
            $this->error("Event with UUID {$uuid} not found.");
            return 1;
        }

        if (!$log->event_class || !class_exists($log->event_class)) {
            $this->error("Event class [{$log->event_class}] invalid or missing for UUID {$uuid}.");
            return 1;
        }

        $this->info("Replaying event: {$log->event_name} ({$log->event_class})");

        // Reconstruct event
        // Assuming constructor accepts payload array named $data or similar, or we map it.
        // For BaseEvent implementations, we often need to ensure the constructor structure is compatible.
        // For simplicity, we assume the event can be reconstructed from the payload.
        // A more robust way is to have a static ::fromPayload($payload) method on DomainEvent.

        try {
            // Primitive reconstruction attempt
            if (method_exists($log->event_class, 'fromPayload')) {
                $event = call_user_func([$log->event_class, 'fromPayload'], $log->payload);
            } else {
                // Fallback: try passing payload as first argument (common pattern)
                $event = new $log->event_class($log->payload);
            }

            // We might want to preserve the original ID or generate a new one. 
            // Ideally, a replay is a NEW occurrence of the same fact, or a retry of the original.
            // If it's a retry, we keep ID. If it's a re-broadcast, maybe new ID.
            // But BaseEvent constructor generates new ID. 
            // Let's allow new ID for now to distinguish the replay action in logs.

            $this->dispatcher->dispatch($event);

            $this->info("Event dispatched successfully.");

        } catch (\Throwable $e) {
            $this->error("Failed to reconstruct or dispatch event: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}

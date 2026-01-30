<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\Automation\AutomationService;
use App\Models\Automation;

class HandleAutomationAction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $automationId;
    protected $stepId;
    protected $actionConfig;
    protected $eventData;

    /**
     * Create a new job instance.
     */
    public function __construct(int $automationId, int $stepId, array $actionConfig, array $eventData)
    {
        $this->automationId = $automationId;
        $this->stepId = $stepId;
        $this->actionConfig = $actionConfig;
        $this->eventData = $eventData;
    }

    /**
     * Execute the job.
     */
    public function handle(AutomationService $automationService): void
    {
        $automation = \App\Models\Automation::find($this->automationId);
        if (!$automation || !$automation->is_active) {
            return;
        }

        $step = \App\Models\AutomationStep::find($this->stepId);
        if (!$step) {
            return;
        }

        // Check for Halt policy in previous steps of this trace
        if ($automationService->isTraceHalted($this->automationId, $this->eventData['id'])) {
            \Illuminate\Support\Facades\Log::info("Automation: Job halted for trace {$this->eventData['id']}");
            return;
        }

        $action = $step->action;
        if (!$action) {
            return;
        }

        // Reconstruct a simple event container for the service
        $event = new class ($this->eventData) implements \App\Core\Events\DomainEvent {
            protected $data;
            public function __construct($data)
            {
                $this->data = $data;
            }
            public function getEventId(): string
            {
                return $this->data['id'] ?? (string) \Illuminate\Support\Str::uuid();
            }
            public function getEventName(): string
            {
                return $this->data['name'];
            }
            public function getOccurredOn(): \DateTimeImmutable
            {
                return new \DateTimeImmutable($this->data['occurred_at']);
            }
            public function getPayload(): array
            {
                return $this->data['payload'];
            }
            public function getEntityType(): string
            {
                return $this->data['entity_type'] ?? 'unknown';
            }
            public function getEntityId(): int|string|null
            {
                return $this->data['entity_id'] ?? null;
            }
            public function getTriggeredBy(): ?int
            {
                return $this->data['triggered_by'] ?? null;
            }
            public function getSource(): string
            {
                return $this->data['source'] ?? 'web';
            }
            public function getPriority(): string
            {
                return $this->data['priority'] ?? \App\Core\Events\DomainEvent::PRIORITY_NORMAL;
            }
        };

        try {
            $automationService->executeRelationalAction($action, $event, $automation, $step);
        } catch (\Exception $e) {
            if ($this->attempts() < ($step->retry_limit ?? 0) + 1) {
                // Exponential backoff
                $delay = pow(10, $this->attempts());
                $this->release($delay);
                return;
            }

            // If we reached retry limit, the failure is already logged by executeRelationalAction
            throw $e;
        }
    }

    /**
     * Determine the number of times the job may be attempted.
     */
    public function tries()
    {
        // We handle retry limit manually in handle() to use the database value
        // But we set a high enough limit here to allow manual release()
        return 10;
    }
}

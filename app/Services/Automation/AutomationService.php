<?php

namespace App\Services\Automation;

use App\Core\Events\DomainEvent;
use App\Models\Automation;
use App\Models\AutomationTrigger;
use App\Models\AutomationStep;
use App\Models\AutomationCondition;
use App\Models\AutomationExecutionLog;
use App\Jobs\HandleAutomationAction;
use App\Services\Mail\EmailDispatcher;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class AutomationService
{
    protected $emailDispatcher;

    public function __construct(EmailDispatcher $emailDispatcher)
    {
        $this->emailDispatcher = $emailDispatcher;
    }

    /**
     * Evaluate rules for the given event.
     */
    public function evaluate(DomainEvent $event): void
    {
        $eventName = $event->getEventName();
        $automations = Automation::active()
            ->current()
            ->whereHas('triggers', function ($query) use ($eventName) {
                $query->where('event_name', $eventName);
            })->get();

        foreach ($automations as $automation) {
            // Safety Controls
            if (!$this->checkSafetyControls($automation, $event)) {
                continue;
            }

            // Global Conditions
            if ($this->matchesRelationalConditions($event, $automation->globalConditions, $automation->condition_logic)) {
                $this->executeRelationalActions($automation->steps, $event, $automation);
            }
        }
    }

    /**
     * Check if the event matches the rule conditions.
     */
    protected function matchesRelationalConditions(DomainEvent $event, $conditions, string $logic = 'AND'): bool
    {
        if ($conditions->isEmpty()) {
            return true;
        }

        $payload = $event->getPayload();
        $entity = null; // Lazy loaded
        $results = [];

        foreach ($conditions as $condition) {
            $isMatch = false;

            $type = $condition->type;
            $field = $condition->field;
            $operator = $condition->operator ?? '=';
            $expected = $condition->value;

            if ($type === 'payload') {
                $actual = $payload[$field] ?? null;
            } elseif ($type === 'entity') {
                $entity = $entity ?? $this->resolveEntity($event);
                if (is_array($entity)) {
                    $actual = $entity[$field] ?? null;
                } elseif (is_object($entity)) {
                    $actual = $entity->$field ?? null;
                } else {
                    $actual = null;
                }
            } elseif ($type === 'counts') {
                $actual = \App\Models\EventLog::where('event_name', $field)
                    ->where('entity_type', $event->getEntityType())
                    ->where('entity_id', $event->getEntityId())
                    ->count();
            } else {
                $actual = null;
            }

            $isMatch = $this->evaluateCondition($actual, $operator, $expected);
            $results[] = $isMatch;

            // Short-circuit
            if ($logic === 'AND' && !$isMatch)
                return false;
            if ($logic === 'OR' && $isMatch)
                return true;
        }

        return $logic === 'OR' ? in_array(true, $results) : !in_array(false, $results);
    }

    protected function checkAttributes(array $data, array $criteria): bool
    {
        foreach ($criteria as $field => $operatorValue) {
            $actual = $data[$field] ?? null;
            $operator = is_array($operatorValue) ? ($operatorValue['operator'] ?? '=') : '=';
            $expected = is_array($operatorValue) ? ($operatorValue['value'] ?? null) : $operatorValue;

            if (!$this->evaluateCondition($actual, $operator, $expected)) {
                return false;
            }
        }
        return true;
    }

    protected function checkCounts(DomainEvent $event, array $criteria): bool
    {
        foreach ($criteria as $eventName => $operatorValue) {
            $count = \App\Models\EventLog::where('event_name', $eventName)
                ->where('entity_type', $event->getEntityType())
                ->where('entity_id', $event->getEntityId())
                ->count();

            $operator = is_array($operatorValue) ? ($operatorValue['operator'] ?? '=') : '=';
            $expected = is_array($operatorValue) ? ($operatorValue['value'] ?? null) : $operatorValue;

            if (!$this->evaluateCondition($count, $operator, $expected)) {
                return false;
            }
        }
        return true;
    }

    protected function resolveEntity(DomainEvent $event)
    {
        $type = $event->getEntityType();
        $id = $event->getEntityId();

        if (!$type || !$id)
            return null;

        $class = "App\\Models\\" . \Illuminate\Support\Str::studly($type);
        if (class_exists($class)) {
            return $class::find($id);
        }

        return null;
    }

    protected function evaluateCondition($actual, $operator, $expected): bool
    {
        switch ($operator) {
            case '>':
                return $actual > $expected;
            case '<':
                return $actual < $expected;
            case '>=':
                return $actual >= $expected;
            case '<=':
                return $actual <= $expected;
            case '!=':
                return $actual != $expected;
            case 'contains':
                return str_contains((string) $actual, (string) $expected);
            case 'in':
                return is_array($expected) && in_array($actual, $expected);
            case '=':
            default:
                return $actual == $expected;
        }
    }

    /**
     * Execute the actions defined in the rule.
     */
    protected function executeRelationalActions($steps, DomainEvent $event, Automation $automation): void
    {
        foreach ($steps as $step) {
            // Check for Halt policy in previous steps of this trace
            if ($this->isTraceHalted($automation->id, $event->getEventId())) {
                Log::info("Automation: Trace halted for {$event->getEventId()} due to previous step failure.");
                break;
            }

            // Check step-level conditions
            if (!$this->matchesRelationalConditions($event, $step->conditions, $step->condition_logic)) {
                continue;
            }

            $action = $step->action;
            if (!$action)
                continue;

            $delay = $step->delay;

            if ($delay && $delay > 0) {
                $eventData = [
                    'id' => $event->getEventId(),
                    'name' => $event->getEventName(),
                    'occurred_at' => $event->getOccurredOn()->format('c'),
                    'payload' => $event->getPayload(),
                    'entity_type' => $event->getEntityType(),
                    'entity_id' => $event->getEntityId(),
                    'triggered_by' => $event->getTriggeredBy(),
                    'source' => $event->getSource(),
                ];

                HandleAutomationAction::dispatch($automation->id, $step->id, (array) $action->config, $eventData)
                    ->delay(now()->addSeconds($delay));

                $this->recordRelationalLog($automation->id, $step->id, $event->getEventId(), $action->type, 'pending', null, $action->config);
            } else {
                $this->executeRelationalAction($action, $event, $automation, $step);
            }
        }
    }

    /**
     * Execute a single action and log the result.
     */
    public function executeRelationalAction(\App\Models\AutomationAction $action, DomainEvent $event, Automation $automation, AutomationStep $step): void
    {
        $config = (array) $action->config;
        $type = $action->type;

        try {
            // Payload Masking for Logging
            $maskedConfig = $this->maskPayload($config);

            switch ($type) {
                case 'email':
                    $this->handleEmailAction($config, $event);
                    break;
                case 'webhook':
                    $this->handleWebhookAction($config, $event);
                    break;
                case 'notification':
                    $this->handleInternalNotification($config, $event);
                    break;
                case 'status_update':
                    $this->handleStatusUpdate($config, $event);
                    break;
                default:
                    throw new \Exception("Unknown action type: {$type}");
            }

            $this->recordRelationalLog($automation->id, $step->id, $event->getEventId(), $type, 'success', null, $maskedConfig);

        } catch (\Exception $e) {
            Log::error("Automation Action Failed", [
                'automation_id' => $automation->id,
                'step_id' => $step->id,
                'error' => $e->getMessage()
            ]);

            $this->recordRelationalLog($automation->id, $step->id, $event->getEventId(), $type, 'failed', $e->getMessage(), $maskedConfig ?? $config);

            // Rethrow so the Job can handle retries if applicable
            throw $e;
        }
    }

    protected function recordRelationalLog(int $automationId, int $stepId, string $eventId, string $type, string $status, ?string $error = null, ?array $payload = null): void
    {
        AutomationExecutionLog::create([
            'automation_id' => $automationId,
            'automation_step_id' => $stepId,
            'event_id' => $eventId,
            'status' => $status,
            'error' => $error,
            'payload' => $payload,
            'executed_at' => ($status !== 'pending') ? now() : null,
        ]);
    }

    protected function handleEmailAction(array $action, DomainEvent $event): void
    {
        $to = $action['to'] ?? ($event->getPayload()['client_email'] ?? null);
        $subject = $action['subject'] ?? 'Automation Alert';
        $template = $action['template'] ?? 'emails.generic_automation';

        // Merge event payload with action data for template
        $data = array_merge($event->getPayload(), $action['data'] ?? []);

        if ($to) {
            $this->emailDispatcher->dispatch($to, $subject, $template, $data);
        }
    }

    protected function handleWebhookAction(array $action, DomainEvent $event): void
    {
        $url = $action['url'] ?? null;
        if (!$url)
            return;

        Http::post($url, [
            'event' => $event->getEventName(),
            'payload' => $event->getPayload(),
            'metadata' => [
                'event_id' => $event->getEventId(),
                'occurred_at' => $event->getOccurredOn()->format('c'),
            ]
        ]);
    }

    protected function handleInternalNotification(array $action, DomainEvent $event): void
    {
        // Integration with existing Notification model/system
        // Assuming a generic pattern for now
        $userId = $action['user_id'] ?? $event->getTriggeredBy();
        if (!$userId)
            return;

        \App\Models\PendingNotification::create([
            'user_id' => $userId,
            'type' => 'automation',
            'data' => [
                'message' => $action['message'] ?? "Automation triggered by {$event->getEventName()}",
                'event_id' => $event->getEventId(),
            ]
        ]);
    }

    /**
     * Check safety controls before executing rule.
     */
    protected function checkSafetyControls(Automation $automation, DomainEvent $event): bool
    {
        $settings = $automation->settings ?? [];

        // 1. Kill Switch
        if (!($settings['is_enabled'] ?? true)) {
            return false;
        }

        // 2. Rate Limiting per Workflow
        if ($limitCount = ($settings['rate_limit_count'] ?? null)) {
            $period = $settings['rate_limit_period'] ?? 1440; // Default 1 day in minutes
            $recentCount = AutomationExecutionLog::where('automation_id', $automation->id)
                ->where('status', '!=', 'failed')
                ->where('executed_at', '>=', now()->subMinutes($period))
                ->count();

            if ($recentCount >= $limitCount) {
                Log::warning("Automation: Rate limit reached for rule {$automation->id}");
                return false;
            }
        }

        // 3. Max Executions per Entity
        if ($maxExecutions = ($settings['max_executions_per_entity'] ?? null)) {
            $entityType = $event->getEntityType();
            $entityId = $event->getEntityId();

            if ($entityType && $entityId) {
                $executions = AutomationExecutionLog::where('automation_id', $automation->id)
                    ->where('status', 'success')
                    ->whereHas('automation', function ($query) use ($entityType, $entityId) {
                        // This part needs a join with EventLog or recording entity info in AutomationExecutionLog
                        // For now, let's look at the associated EventLog via event_id
                    })->whereIn('event_id', function ($query) use ($entityType, $entityId) {
                        $query->select('event_id')->from('event_logs')
                            ->where('entity_type', $entityType)
                            ->where('entity_id', $entityId);
                    })->count();

                if ($executions >= $maxExecutions) {
                    return false;
                }
            }
        }

        // 4. Loop Prevention
        if ($this->detectLoop($automation, $event)) {
            Log::alert("Automation: Loop detected for rule {$automation->id}");
            return false;
        }

        return true;
    }

    protected function detectLoop(Automation $automation, DomainEvent $event): bool
    {
        static $depth = 0;
        $depth++;
        if ($depth > 10)
            return true;

        $recentExecutions = AutomationExecutionLog::where('automation_id', $automation->id)
            ->where('executed_at', '>=', now()->subMinute())
            ->count();

        return $recentExecutions > 50;
    }

    protected function handleStatusUpdate(array $action, DomainEvent $event): void
    {
        $entity = $this->resolveEntity($event);
        if (!$entity) {
            throw new \Exception("Could not resolve entity for status update");
        }

        $field = $action['field'] ?? 'status';
        $value = $action['value'] ?? null;

        if ($value) {
            $entity->update([$field => $value]);
            Log::info("Automation: Updated entity status", [
                'type' => $event->getEntityType(),
                'id' => $event->getEntityId(),
                $field => $value
            ]);
        }
    }

    public function isTraceHalted(int $automationId, string $eventId): bool
    {
        return AutomationExecutionLog::where('automation_id', $automationId)
            ->where('event_id', $eventId)
            ->where('status', 'failed')
            ->whereHas('step', function ($query) {
                $query->where('on_failure', 'halt');
            })->exists();
    }

    protected function maskPayload(array $payload): array
    {
        $sensitiveFields = ['password', 'token', 'secret', 'key', 'auth', 'ssn', 'api_key'];

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = $this->maskPayload($value);
            } elseif (in_array(strtolower($key), $sensitiveFields)) {
                $payload[$key] = '********';
            }
        }

        return $payload;
    }
}

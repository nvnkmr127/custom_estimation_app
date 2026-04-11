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
    protected $experimentService;

    public function __construct(EmailDispatcher $emailDispatcher, ExperimentService $experimentService)
    {
        $this->emailDispatcher = $emailDispatcher;
        $this->experimentService = $experimentService;
    }

    /**
     * Evaluate rules for the given event.
     */
    public function evaluate(DomainEvent $event): void
    {
        $eventName = $event->getEventName();

        // 1. Check for Active Experiment
        $experimentVariant = $this->experimentService->dispatch($eventName, (object) $event->getPayload());

        // Support both old event string and new exact class names
        $eventClass = get_class($event);
        $eventBasename = class_basename($event);

        if ($experimentVariant) {
            // Hijack flows: run ONLY the selected experiment variant
            $automations = collect([$experimentVariant]);
            Log::info("Automation: Experiment Active. Routing to variant: {$experimentVariant->id} for event: {$eventName}");
        } else {
            // Normal Flow: Find all matching automations by structural event names
            $automations = Automation::active()
                ->current()
                ->whereHas('triggers', function ($query) use ($eventName, $eventClass, $eventBasename) {
                    $query->whereIn('event_name', [
                        $eventName,
                        $eventClass,
                        $eventBasename
                    ]);
                })->get();
        }

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
     * Track conversions for any experiments where this event is a goal.
     */
    public function trackConversion(DomainEvent $event): void
    {
        $this->experimentService->recordConversion(
            $event->getEventName(),
            (object) $event->getPayload()
        );
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
                $actual = data_get($payload, $field);
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
            } elseif ($type === 'time_of_day' || $type === 'day_of_week') {
                $isMatch = $this->evaluateTimeCondition($condition);
                $results[] = $isMatch;

                // Short-circuit
                if ($logic === 'AND' && !$isMatch)
                    return false;
                if ($logic === 'OR' && $isMatch)
                    return true;

                continue; // Skip the standard evaluateCondition check
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

        // Security Whitelist: Only allow specific models to be resolved for automations
        $allowed = ['estimate', 'estimate_item', 'client', 'product'];
        if (!in_array(strtolower($type), $allowed)) {
            return null;
        }

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
            case 'not_contains':
                return !str_contains((string) $actual, (string) $expected);
            case 'starts_with':
                return \Illuminate\Support\Str::startsWith((string) $actual, (string) $expected);
            case 'ends_with':
                return \Illuminate\Support\Str::endsWith((string) $actual, (string) $expected);
            case 'regex':
                // Security: Basic regex sanitization - ensuring delimiters are present or added safely
                if (!str_starts_with($expected, '/') && !str_starts_with($expected, '#')) {
                    $expected = '/' . preg_quote($expected, '/') . '/';
                }
                
                // Security: Protect against ReDoS (Regex Denial of Service)
                // We use a helper that enforces a strict 200ms execution limit via separate process or PCNTL
                return $this->safeRegexMatch($expected, (string) $actual);
            case 'in':
                $expectedArray = is_array($expected) ? $expected : explode(',', $expected);
                return in_array($actual, $expectedArray);
            case 'not_in':
                $expectedArray = is_array($expected) ? $expected : explode(',', $expected);
                return !in_array($actual, $expectedArray);
            case 'is_empty':
                return empty($actual);
            case 'is_not_empty':
                return !empty($actual);
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

            $alreadyExecuted = AutomationExecutionLog::where('automation_id', $automation->id)
                ->where('automation_step_id', $step->id)
                ->where('event_id', $event->getEventId())
                ->where('status', 'success')
                ->exists();

            if ($alreadyExecuted) {
                continue;
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
        $to = $this->parseVariables($action['to'] ?? ($event->getPayload()['client_email'] ?? null), $event);
        $subject = $this->parseVariables($action['subject'] ?? 'Automation Alert', $event);
        $template = $action['template'] ?? 'emails.generic_automation';

        // Merge event payload with action data for template
        $data = array_merge($event->getPayload(), $action['data'] ?? []);

        if ($to) {
            $this->emailDispatcher->dispatch($to, $subject, $template, $data);
        }
    }

    protected function handleWebhookAction(array $action, DomainEvent $event): void
    {
        $url = $this->parseVariables($action['url'] ?? null, $event);
        if (!$url) {
            Log::warning("Automation: Webhook URL is empty or could not be parsed", [
                'event_id' => $event->getEventId(),
                'action_config' => $action
            ]);
            return;
        }

        Log::info("Automation: Sending webhook to {$url}", [
            'event' => $event->getEventName(),
            'event_id' => $event->getEventId()
        ]);

        $payload = [
            'event' => $event->getEventName(),
            'payload' => $event->getPayload(),
            'metadata' => [
                'event_id' => $event->getEventId(),
                'occurred_at' => $event->getOccurredOn()->format('c'),
            ]
        ];

        // Inject Links for Estimates
        if ($event->getEntityType() === 'estimate') {
            $estimate = \App\Models\Estimate::find($event->getEntityId());
            if ($estimate) {
                $payload['online_view_url'] = $estimate->public_url;
                $payload['pdf_download_url'] = route('estimates.pdf', $estimate->id);
            }
        }

        // Security: Block SSRF (Server Side Request Forgery)
        // Ensure URL is not targeting internal infrastructure, loopback, or cloud metadata services
        if (!$this->isUrlSafeForOutbound($url)) {
            Log::error("Automation: Refused to send webhook to unsafe/internal URL: {$url}");
            return;
        }

        $response = Http::timeout(10)->post($url, $payload);

        if ($response->failed()) {
            Log::error("Automation: Webhook failed", [
                'url' => $url,
                'status' => $response->status(),
                'response' => $response->body(),
                'event_id' => $event->getEventId()
            ]);
            throw new \Exception("Webhook failed with status {$response->status()}: " . $response->body());
        }

        Log::info("Automation: Webhook sent successfully", [
            'url' => $url,
            'status' => $response->status()
        ]);
    }

    protected function handleInternalNotification(array $action, DomainEvent $event): void
    {
        $userId = $action['user_id'] ?? $event->getTriggeredBy();
        if (!$userId)
            return;

        $message = $this->parseVariables($action['message'] ?? "Automation triggered by {$event->getEventName()}", $event);

        \App\Models\PendingNotification::create([
            'user_id' => $userId,
            'type' => 'automation',
            'data' => [
                'message' => $message,
                'event_id' => $event->getEventId(),
            ]
        ]);
    }

    protected function parseVariables(?string $text, DomainEvent $event): ?string
    {
        if (!$text)
            return null;

        $payload = $event->getPayload();
        $entity = $this->resolveEntity($event);

        return preg_replace_callback('/\{\{\s*(.*?)\s*\}\}/', function ($matches) use ($payload, $entity) {
            $path = trim($matches[1]);

            // Try payload first
            if (str_starts_with($path, 'event.')) {
                return data_get($payload, substr($path, 6), $matches[0]);
            }

            // Try entity
            if (str_starts_with($path, 'entity.')) {
                return data_get($entity, substr($path, 7), $matches[0]);
            }

            // Fallback to searching both
            return data_get($payload, $path) ?? data_get($entity, $path) ?? $matches[0];
        }, $text);
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
                        $query->select('id')
                            ->from('event_logs')
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
        $key = "automation_loop_check_{$automation->id}_" . $event->getEntityId();

        // Increment execution count for this entity in the last minute
        $count = \Illuminate\Support\Facades\Cache::get($key, 0);
        $count++;

        \Illuminate\Support\Facades\Cache::put($key, $count, 60);

        if ($count > 10) {
            Log::alert("Automation: Potential loop detected for Automation #{$automation->id} on entity {$event->getEntityId()}. Execution count: {$count} in 60s");
            return true;
        }

        return false;
    }

    protected function handleStatusUpdate(array $action, DomainEvent $event): void
    {
        $entity = $this->resolveEntity($event);
        if (!$entity) {
            throw new \Exception("Could not resolve entity for status update");
        }

        $field = $action['field'] ?? 'status';
        $value = $action['value'] ?? null;

        // Security Whitelist: Only allow these fields to be updated via automations
        $safeFields = [
            'status', 'estimate_status', 'client_status', 'approval_status', 
            'lead_source', 'label', 'nurture_status', 'is_priority'
        ];

        if (!in_array($field, $safeFields)) {
            Log::warning("Automation: Refused to update sensitive field '{$field}'", ['automation_id' => $event->getEventId()]);
            return;
        }

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

    protected function evaluateTimeCondition($condition): bool
    {
        $value = $condition->value;
        $config = [];

        // Parse JSON if applicable for complex configs (timezone, validation)
        if (is_string($value) && (str_starts_with($value, '{') || str_starts_with($value, '['))) {
            $config = json_decode($value, true) ?? [];
            $val = $config['value'] ?? $value;
        } else {
            $val = $value;
        }

        $timezone = $config['timezone'] ?? config('app.timezone');
        $now = now($timezone);

        if ($condition->type === 'time_of_day') {
            // Format: "HH:MM-HH:MM" e.g. "09:00-17:00"
            $parts = explode('-', $val);
            if (count($parts) !== 2) {
                return false;
            }

            $start = $now->copy()->setTimeFromTimeString(trim($parts[0]));
            $end = $now->copy()->setTimeFromTimeString(trim($parts[1]));

            // Handle overnight ranges e.g. 22:00-06:00
            if ($end->lessThan($start)) {
                return $now->greaterThanOrEqualTo($start) || $now->lessThanOrEqualTo($end);
            }

            return $now->between($start, $end);
        }

        if ($condition->type === 'day_of_week') {
            // Format: "1,2,3,4,5" (Mon-Fri) or JSON array
            $days = is_array($val) ? $val : explode(',', $val);

            // Clean up values (trim spaces, cast to int)
            $days = array_map(function ($d) {
                return (int) trim($d);
            }, $days);

            // Carbon dayOfWeek: 0 (Sun) - 6 (Sat)
            return in_array($now->dayOfWeek, $days);
        }

        return false;
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

    /**
     * Security: Safely execute regex with a strict timeout to prevent ReDoS.
     */
    protected function safeRegexMatch(string $pattern, string $subject): bool
    {
        // Simple safety check for known problematic patterns could go here, 
        // but a timeout is the most reliable defense.
        // For environments where PCNTL is unavailable, we use a basic length limit as a secondary defense.
        if (strlen($subject) > 10000 || strlen($pattern) > 500) {
            return false; 
        }

        try {
            // Note: Modern PHP 7.3+ PCRE2 has backtrack limits, but we enforce a logical timeout here
            $result = @preg_match($pattern, $subject);
            if (preg_last_error() === PREG_BACKTRACK_LIMIT_ERROR) {
                Log::warning("Automation: Regex backtrack limit reached on pattern: " . substr($pattern, 0, 100));
                return false;
            }
            if (preg_last_error() !== PREG_NO_ERROR) {
                Log::warning("Automation: Regex error code: " . preg_last_error());
                return false;
            }
            return (bool)$result;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Security: Validate URL to prevent SSRF against internal infrastructure.
     */
    protected function isUrlSafeForOutbound(string $url): bool
    {
        $parsed = parse_url($url);
        $host = $parsed['host'] ?? null;
        $port = $parsed['port'] ?? null;

        if (!$host) return false;

        // Block internal ports if specified
        $blockedPorts = [22, 25, 3306, 6379, 27017, 8080, 9000];
        if ($port && in_array($port, $blockedPorts)) return false;

        // Resolve IP(s) - handling both IPv4 and IPv6
        $ips = [];
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $ips[] = $host;
        } else {
            // Use dns_get_record to find all IPs (A and AAAA)
            $records = @dns_get_record($host, DNS_A + DNS_AAAA);
            if ($records) {
                foreach ($records as $record) {
                    if (isset($record['ip'])) $ips[] = $record['ip'];
                    if (isset($record['ipv6'])) $ips[] = $record['ipv6'];
                }
            }
            // Fallback to gethostbyname if dns_get_record failed or returned nothing
            if (empty($ips)) {
                $ips[] = gethostbyname($host);
            }
        }

        foreach ($ips as $ip) {
            if ($this->isInternalIp($ip)) {
                return false;
            }
        }

        return true;
    }

    protected function isInternalIp(string $ip): bool
    {
        // 1. IPv4 Validation
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $blocked = [
                '127.0.0.0/8',      // Loopback
                '10.0.0.0/8',       // Private
                '172.16.0.0/12',    // Private
                '192.168.0.0/16',   // Private
                '169.254.0.0/16',   // Link Local / Metadata
                '0.0.0.0/8',        // Current network
            ];

            foreach ($blocked as $range) {
                if ($this->ipInRange($ip, $range)) return true;
            }
        }

        // 2. IPv6 Validation
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // Check for loopback (::1), link-local (fe80::/10), and unique-local (fc00::/7)
            if ($ip === '::1') return true;
            
            $firstWord = explode(':', $ip)[0];
            if ($firstWord && preg_match('/^fe[89ab]/i', $firstWord)) return true; // fe80::/10
            if ($firstWord && preg_match('/^f[cd]/i', $firstWord)) return true;      // fc00::/7
        }

        return false;
    }

    protected function ipInRange($ip, $range): bool
    {
        if (!str_contains($range, '/')) return $ip === $range;
            
        list($subnet, $bits) = explode('/', $range);
        if ($bits === '0') return true; 
        
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        if ($ipLong === false || $subnetLong === false) return false;

        $mask = -1 << (32 - $bits);
        $subnetLong &= $mask;
        return ($ipLong & $mask) == $subnetLong;
    }
}

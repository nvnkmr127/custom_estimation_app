<?php

namespace App\Services\Automation;

use App\Models\Automation;
use App\Models\AutomationTemplate;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TemplateService
{
    /**
     * Create a new template from an existing automation.
     */
    public function createFromAutomation(Automation $automation, string $name, ?string $description = null, ?string $category = null, ?User $user = null): AutomationTemplate
    {
        // Eager load everything needed for a full export
        $automation->load([
            'triggers',
            'globalConditions',
            'steps.action',
            'steps.conditions',
            'schedules' // Include schedules in template? Usually yes.
        ]);

        $structure = $this->serializeAutomation($automation);

        return AutomationTemplate::create([
            'name' => $name,
            'description' => $description ?? $automation->description,
            'category' => $category,
            'structure' => $structure,
            'created_by' => $user ? $user->id : null,
            'is_system' => false
        ]);
    }

    /**
     * Install a template, creating a new automation for the user/system.
     */
    public function install(AutomationTemplate $template, User $user, array $overrides = []): Automation
    {
        return DB::transaction(function () use ($template, $user, $overrides) {
            $structure = $template->structure;

            // 1. Create Automation
            $automationData = [
                'name' => $overrides['name'] ?? ($structure['name'] . ' (Copy)'),
                'description' => $structure['description'] ?? $template->description,
                'is_active' => false, // Default to inactive upon install
                'condition_logic' => $structure['condition_logic'] ?? 'AND',
                'settings' => $structure['settings'] ?? [],
                'last_edited_by' => $user->id,
                'last_edited_at' => now(),
            ];

            $automation = Automation::create($automationData);

            // 2. Recreate Triggers
            foreach ($structure['triggers'] ?? [] as $triggerData) {
                $automation->triggers()->create([
                    'event_name' => $triggerData['event_name'],
                    'provider' => $triggerData['provider'] ?? 'system',
                    'config' => $triggerData['config'] ?? [],
                ]);
            }

            // 3. Recreate Global Conditions
            foreach ($structure['global_conditions'] ?? [] as $conditionData) {
                $automation->globalConditions()->create([
                    'type' => $conditionData['type'],
                    'field' => $conditionData['field'] ?? null,
                    'operator' => $conditionData['operator'] ?? '=',
                    'value' => $conditionData['value'],
                ]);
            }

            // 4. Recreate Steps (and their Actions + Conditions)
            foreach ($structure['steps'] ?? [] as $stepData) {
                $step = $automation->steps()->create([
                    'name' => $stepData['name'] ?? 'Untitled Step',
                    'description' => $stepData['description'] ?? null,
                    'condition_logic' => $stepData['condition_logic'] ?? 'AND',
                    'order' => $stepData['order'] ?? 1,
                    'delay' => $stepData['delay'] ?? 0,
                    'retry_limit' => $stepData['retry_limit'] ?? 0,
                    'on_failure' => $stepData['on_failure'] ?? 'continue',
                    'is_enabled' => $stepData['is_enabled'] ?? true,
                ]);

                // 4a. Step Action
                if (isset($stepData['action'])) {
                    $step->action()->create([
                        'type' => $stepData['action']['type'],
                        'config' => $stepData['action']['config'] ?? [],
                    ]);
                }

                // 4b. Step Conditions
                foreach ($stepData['conditions'] ?? [] as $stepCondition) {
                    $step->conditions()->create([
                        'automation_id' => $automation->id, // Redundant but schema requires it
                        'type' => $stepCondition['type'],
                        'field' => $stepCondition['field'] ?? null,
                        'operator' => $stepCondition['operator'] ?? '=',
                        'value' => $stepCondition['value'],
                    ]);
                }
            }

            // 5. Recreate Schedules
            foreach ($structure['schedules'] ?? [] as $scheduleData) {
                $automation->schedules()->create([
                    'name' => $scheduleData['name'] ?? null,
                    'cron_expression' => $scheduleData['cron_expression'],
                    'timezone' => $scheduleData['timezone'] ?? 'UTC',
                    'is_active' => false, // Default schedules to inactive
                    'metadata' => $scheduleData['metadata'] ?? null,
                ]);
            }

            return $automation;
        });
    }

    public function getSystemTemplates(): Collection
    {
        return AutomationTemplate::where('is_system', true)->get();
    }

    public function getUserTemplates(User $user): Collection
    {
        return AutomationTemplate::where('created_by', $user->id)
            ->orWhere('is_system', true)
            ->get();
    }

    /**
     * Serialize relation-heavy Automation model into a flat array structure.
     */
    protected function serializeAutomation(Automation $automation): array
    {
        return [
            'name' => $automation->name,
            'description' => $automation->description,
            'condition_logic' => $automation->condition_logic,
            'settings' => $automation->settings,
            'triggers' => $automation->triggers->map(fn($t) => [
                'event_name' => $t->event_name,
                'provider' => $t->provider,
                'config' => $t->config,
            ])->toArray(),
            'global_conditions' => $automation->globalConditions->map(fn($c) => [
                'type' => $c->type,
                'field' => $c->field,
                'operator' => $c->operator,
                'value' => $c->value,
            ])->toArray(),
            'steps' => $automation->steps->map(fn($s) => [
                'name' => $s->name,
                'description' => $s->description,
                'condition_logic' => $s->condition_logic,
                'order' => $s->order,
                'delay' => $s->delay,
                'retry_limit' => $s->retry_limit,
                'on_failure' => $s->on_failure,
                'is_enabled' => $s->is_enabled,
                'action' => $s->action ? [
                    'type' => $s->action->type,
                    'config' => $s->action->config,
                ] : null,
                'conditions' => $s->conditions->map(fn($c) => [
                    'type' => $c->type,
                    'field' => $c->field,
                    'operator' => $c->operator,
                    'value' => $c->value,
                ])->toArray(),
            ])->toArray(),
            'schedules' => $automation->schedules->map(fn($sch) => [
                'name' => $sch->name,
                'cron_expression' => $sch->cron_expression,
                'timezone' => $sch->timezone,
                'metadata' => $sch->metadata,
            ])->toArray(),
        ];
    }
}

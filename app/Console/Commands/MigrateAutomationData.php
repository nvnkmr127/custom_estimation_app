<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigrateAutomationData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-automation-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate data from automation_rules (JSON) to normalized relational tables';

    public function handle()
    {
        $this->info('Starting automation data migration...');

        $legacyRules = \App\Models\AutomationRule::all();

        foreach ($legacyRules as $legacyRule) {
            $this->info("Migrating rule: {$legacyRule->name}");

            // 1. Create Automation (Root)
            $automation = \App\Models\Automation::create([
                'id' => $legacyRule->id,
                'name' => $legacyRule->name,
                'condition_logic' => $legacyRule->condition_logic ?? 'AND',
                'settings' => $legacyRule->settings,
                'is_active' => $legacyRule->is_active,
                'created_at' => $legacyRule->created_at,
                'updated_at' => $legacyRule->updated_at,
            ]);

            // 2. Create Trigger
            \App\Models\AutomationTrigger::create([
                'automation_id' => $automation->id,
                'event_name' => $legacyRule->trigger_event,
            ]);

            // 3. Create Global Conditions
            if (!empty($legacyRule->conditions)) {
                foreach ($legacyRule->conditions as $condition) {
                    \App\Models\AutomationCondition::create([
                        'automation_id' => $automation->id,
                        'type' => $condition['type'] ?? 'payload',
                        'field' => $condition['field'] ?? '',
                        'operator' => $condition['operator'] ?? '=',
                        'value' => $condition['value'] ?? '',
                    ]);
                }
            }

            // 4. Create Steps & Actions
            if (!empty($legacyRule->actions)) {
                foreach ($legacyRule->actions as $index => $actionData) {
                    $step = \App\Models\AutomationStep::create([
                        'automation_id' => $automation->id,
                        'order' => $index,
                        'delay' => $actionData['delay'] ?? 0,
                        'condition_logic' => $actionData['condition_logic'] ?? 'AND',
                    ]);

                    \App\Models\AutomationAction::create([
                        'automation_step_id' => $step->id,
                        'type' => $actionData['type'],
                        'config' => $actionData['config'] ?? $actionData, // Fallback if config is missing
                    ]);

                    // Step-level conditions
                    if (!empty($actionData['conditions'])) {
                        foreach ($actionData['conditions'] as $condition) {
                            \App\Models\AutomationCondition::create([
                                'automation_step_id' => $step->id,
                                'type' => $condition['type'] ?? 'payload',
                                'field' => $condition['field'] ?? '',
                                'operator' => $condition['operator'] ?? '=',
                                'value' => $condition['value'] ?? '',
                            ]);
                        }
                    }
                }
            }
        }

        $this->info('Migrating logs...');
        $legacyLogs = \App\Models\AutomationLog::all();
        foreach ($legacyLogs as $log) {
            // Find the related automation
            $automation = \App\Models\Automation::find($log->automation_rule_id);
            if (!$automation)
                continue;

            // Find the step (this is tricky as legacy logs don't have step IDs)
            // We'll pick the first step for simplicity or leave it null if it's optional
            $step = $automation->steps->first();

            \App\Models\AutomationExecutionLog::create([
                'automation_id' => $automation->id,
                'automation_step_id' => $step ? $step->id : 0, // Fallback
                'event_id' => $log->event_id,
                'status' => $log->status,
                'error' => $log->error,
                'payload' => $log->payload,
                'executed_at' => $log->executed_at,
            ]);
        }

        $this->info('Migration completed successfully!');
    }
}

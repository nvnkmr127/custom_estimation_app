<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Automation;
use App\Models\AutomationTrigger;
use App\Models\AutomationStep;
use App\Models\AutomationCondition;
use App\Models\AutomationAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AutomationController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Automation::class);

        // Load with relationships to avoid N+1 issues in potential future UI enhancements
        $rules = Automation::current()
            ->with(['triggers', 'steps.action', 'globalConditions'])
            ->latest()
            ->get();

        $events = [
            'estimate.created',
            'estimate.sent',
            'estimate.viewed',
            'estimate.accepted',
            'estimate.declined',
            'approval.requested',
            'approval.approved',
            'approval.rejected',
            'comment.added',
            'user.registered',
        ];

        return view('admin.automation.index', compact('rules', 'events'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'trigger_event' => 'required|string',
            'condition_logic' => 'nullable|string',
            'conditions' => 'nullable|array',
            'actions' => 'required|array|min:1',
            'settings' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $this->authorize('create', Automation::class);

        $automation = DB::transaction(function () use ($validated) {
            $automation = Automation::create([
                'name' => $validated['name'],
                'condition_logic' => $validated['condition_logic'] ?? 'AND',
                'settings' => $validated['settings'] ?? [],
                'is_active' => $validated['is_active'] ?? true,
                'version' => 1,
                'is_current_version' => true,
                'created_by' => auth()->id(),
            ]);

            $this->syncAutomationRelations($automation, $validated);

            return $automation;
        });

        \App\Models\ActivityLog::log('automation.create', $automation, "Created automation rule: {$automation->name}");

        return redirect()->route('automation.index')->with('success', 'Automation created successfully.');
    }

    public function edit(Automation $automation)
    {
        $automation->load(['triggers', 'steps.conditions', 'steps.action', 'globalConditions']);

        // Transform the automation to the flat structure the UI expects
        $rule = [
            'id' => $automation->id,
            'name' => $automation->name,
            'trigger_event' => $automation->triggers->first()->event_name ?? '',
            'condition_logic' => $automation->condition_logic,
            'settings' => $automation->settings,
            'is_active' => $automation->is_active,
            'conditions' => $automation->globalConditions->map(function ($c) {
                return ['type' => $c->type, 'field' => $c->field, 'operator' => $c->operator, 'value' => $c->value];
            })->toArray(),
            'actions' => $automation->steps->map(function ($s) {
                return [
                    'type' => $s->action->type ?? '',
                    'delay' => $s->delay,
                    'condition_logic' => $s->condition_logic,
                    'conditions' => $s->conditions->map(function ($c) {
                        return ['type' => $c->type, 'field' => $c->field, 'operator' => $c->operator, 'value' => $c->value];
                    })->toArray(),
                    'config' => $s->action->config ?? [],
                ];
            })->toArray(),
        ];

        return view('admin.automation.index', ['rules' => Automation::latest()->get(), 'events' => $this->getEvents(), 'editingRule' => $rule]);
    }

    protected function getEvents()
    {
        return [
            'estimate.created',
            'estimate.sent',
            'estimate.viewed',
            'estimate.accepted',
            'estimate.declined',
            'approval.requested',
            'approval.approved',
            'approval.rejected',
            'comment.added',
            'user.registered',
            'estimate.expired',
        ];
    }

    public function update(Request $request, Automation $automation)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'trigger_event' => 'required|string',
            'condition_logic' => 'nullable|string',
            'conditions' => 'nullable|array',
            'actions' => 'required|array|min:1',
            'settings' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $this->authorize('update', $automation);

        DB::transaction(function () use ($validated, $automation) {
            $automation->update([
                'name' => $validated['name'],
                'condition_logic' => $validated['condition_logic'] ?? 'AND',
                'settings' => $validated['settings'] ?? [],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Clear existing relations before syncing
            $automation->triggers()->delete();
            $automation->globalConditions()->delete();
            $automation->steps()->each(function (\App\Models\AutomationStep $step) {
                $step->conditions()->delete();
                $step->action()->delete();
                $step->delete();
            });

            $this->syncAutomationRelations($automation, $validated);
        });

        \App\Models\ActivityLog::log('automation.update', $automation, "Updated automation rule: {$automation->name}");

        return redirect()->route('automation.index')->with('success', 'Automation updated successfully.');
    }

    /**
     * Syncs triggers, conditions, and actions for an automation rule.
     */
    private function syncAutomationRelations(Automation $automation, array $validated)
    {
        // Trigger
        AutomationTrigger::create([
            'automation_id' => $automation->id,
            'event_name' => $validated['trigger_event'],
        ]);

        // Global Conditions
        if (!empty($validated['conditions'])) {
            foreach ($validated['conditions'] as $cond) {
                AutomationCondition::create([
                    'automation_id' => $automation->id,
                    'type' => $cond['type'] ?? 'payload',
                    'field' => $cond['field'] ?? '',
                    'operator' => $cond['operator'] ?? '=',
                    'value' => $cond['value'] ?? '',
                ]);
            }
        }

        // Steps & Actions
        foreach ($validated['actions'] as $index => $actionData) {
            $step = AutomationStep::create([
                'automation_id' => $automation->id,
                'order' => $index,
                'delay' => $actionData['delay'] ?? 0,
                'retry_limit' => $actionData['retry_limit'] ?? 0,
                'on_failure' => $actionData['on_failure'] ?? 'continue',
                'condition_logic' => $actionData['condition_logic'] ?? 'AND',
            ]);

            AutomationAction::create([
                'automation_step_id' => $step->id,
                'type' => $actionData['type'],
                'config' => $actionData['config'] ?? array_diff_key($actionData, array_flip(['type', 'delay', 'retry_limit', 'on_failure', 'condition_logic', 'conditions'])),
            ]);

            if (!empty($actionData['conditions'])) {
                foreach ($actionData['conditions'] as $cond) {
                    AutomationCondition::create([
                        'automation_step_id' => $step->id,
                        'type' => $cond['type'] ?? 'payload',
                        'field' => $cond['field'] ?? '',
                        'operator' => $cond['operator'] ?? '=',
                        'value' => $cond['value'] ?? '',
                    ]);
                }
            }
        }
    }

    public function destroy(Automation $automation)
    {
        $this->authorize('delete', $automation);

        $name = $automation->name;
        $id = $automation->id;
        $automation->delete();

        \App\Models\ActivityLog::log('automation.delete', null, "Deleted automation rule: {$name} (ID: {$id})");

        return redirect()->route('automation.index')->with('success', 'Automation deleted successfully.');
    }

    public function getLogs(Automation $automation)
    {
        // Execution logs grouped by event_id (Execution Trace)
        $logs = \App\Models\AutomationExecutionLog::where('automation_id', $automation->id)
            ->with('step.action')
            ->latest()
            ->get()
            ->groupBy('event_id');

        return response()->json($logs);
    }

    public function getMetrics(Automation $automation)
    {
        $logs = \App\Models\AutomationExecutionLog::where('automation_id', $automation->id)->get();
        $totalTriggers = $logs->unique('event_id')->count();
        $totalSteps = $logs->count();
        $successSteps = $logs->where('status', 'success')->count();
        $failureSteps = $logs->where('status', 'failed')->count();

        // Conversion indicators (if tied to estimates)
        $conversion = [
            'opened' => 0,
            'accepted' => 0,
            'total_entities' => 0
        ];

        // This is a simplified heuristic: look for unique entities in logs and check their analytics
        $entities = $logs->whereNotNull('payload')->map(function ($log) {
            $p = (array) $log->payload;
            return ['type' => $p['entity_type'] ?? null, 'id' => $p['entity_id'] ?? null];
        })->filter(fn($e) => $e['type'] === 'estimate' && $e['id'])->unique('id');

        $conversion['total_entities'] = $entities->count();

        if ($entities->count() > 0) {
            $estimateIds = $entities->pluck('id')->toArray();
            $conversion['opened'] = \App\Models\Estimate::whereIn('id', $estimateIds)->where('view_count', '>', 0)->count();
            $conversion['accepted'] = \App\Models\Estimate::whereIn('id', $estimateIds)->where('status', 'accepted')->count();
        }

        return response()->json([
            'trigger_count' => $totalTriggers,
            'completion_rate' => $totalTriggers > 0 ? round(($successSteps / max(1, $totalSteps)) * 100, 2) : 0,
            'failure_rate' => $totalSteps > 0 ? round(($failureSteps / $totalSteps) * 100, 2) : 0,
            'conversion' => $conversion
        ]);
    }

    public function createVersion(Automation $automation)
    {
        $this->authorize('update', $automation);

        $newVersion = DB::transaction(function () use ($automation) {
            // 1. Mark current version as old
            $automation->update(['is_current_version' => false]);

            // 2. Clone basic attributes
            $newVersion = $automation->replicate();
            $newVersion->version = $automation->version + 1;
            $newVersion->parent_id = $automation->parent_id ?: $automation->id;
            $newVersion->is_current_version = true;
            $newVersion->created_by = auth()->id();
            $newVersion->save();

            // 3. Clone Triggers
            foreach ($automation->triggers as $trigger) {
                $newTrigger = $trigger->replicate();
                $newTrigger->automation_id = $newVersion->id;
                $newTrigger->save();
            }

            // 4. Clone Global Conditions
            foreach ($automation->globalConditions as $condition) {
                $newCondition = $condition->replicate();
                $newCondition->automation_id = $newVersion->id;
                $newCondition->save();
            }

            // 5. Clone Steps, Actions, and Step Conditions
            foreach ($automation->steps as $step) {
                $newStep = $step->replicate();
                $newStep->automation_id = $newVersion->id;
                $newStep->save();

                if ($step->action) {
                    $newAction = $step->action->replicate();
                    $newAction->automation_step_id = $newStep->id;
                    $newAction->save();
                }

                foreach ($step->conditions as $stepCondition) {
                    $newStepCondition = $stepCondition->replicate();
                    $newStepCondition->automation_step_id = $newStep->id;
                    $newStepCondition->save();
                }
            }

            return $newVersion;
        });

        \App\Models\ActivityLog::log('automation.version_created', $newVersion, "Created new version {$newVersion->version} of {$newVersion->name}");

        return redirect()->route('automation.index')->with('success', "New version (v{$newVersion->version}) created successfully.");
    }
}

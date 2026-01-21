<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Automation;
use Illuminate\Http\Request;

class AutomationVisualizationController extends Controller
{
    /**
     * Generate Mermaid flowchart data for an automation
     */
    /**
     * Generate Mermaid flowchart data for an automation
     */
    public function flowchart(Automation $automation)
    {
        $this->authorize('view', $automation);

        $automation->load(['triggers', 'steps.action', 'steps.conditions', 'globalConditions']);

        $mermaid = $this->generateMermaidGraph(
            $automation->triggers,
            $automation->globalConditions,
            $automation->steps
        );

        return response()->json([
            'mermaid' => $mermaid,
            'automation' => [
                'id' => $automation->id,
                'name' => $automation->name,
                'description' => $automation->description,
            ]
        ]);
    }

    /**
     * Preview visualization from a template structure (JSON)
     */
    public function preview(Request $request)
    {
        $data = $request->validate([
            'structure' => 'required|array',
        ]);

        $structure = $data['structure'];

        // Mock Triggers
        $triggers = collect($structure['triggers'] ?? [])->map(function ($t) {
            return (object) [
                'event_name' => $t['event_name'] ?? 'Unknown',
            ];
        });

        // Mock Global Conditions
        $globalConditions = collect($structure['global_conditions'] ?? [])->map(function ($c) {
            return (object) $c;
        });

        // Mock Steps
        $steps = collect($structure['steps'] ?? [])->map(function ($s) {
            // Mock Action
            $action = isset($s['action']) ? (object) [
                'type' => $s['action']['type'] ?? 'unknown',
                'config' => $s['action']['config'] ?? [],
            ] : null;

            // Mock Conditions
            $conditions = collect($s['conditions'] ?? [])->map(function ($c) {
                return (object) $c;
            });

            return (object) [
                'name' => $s['name'] ?? 'Step',
                'description' => $s['description'] ?? '',
                'is_enabled' => $s['is_enabled'] ?? true,
                'delay' => $s['delay'] ?? 0,
                'action' => $action,
                'conditions' => $conditions,
            ];
        });

        $mermaid = $this->generateMermaidGraph($triggers, $globalConditions, $steps);

        return response()->json([
            'mermaid' => $mermaid,
            'automation' => [
                'id' => 'preview',
                'name' => $structure['name'] ?? 'Template Preview',
                'description' => $structure['description'] ?? '',
            ]
        ]);
    }

    /**
     * Generate timeline data for an automation
     */
    public function timeline(Automation $automation)
    {
        $this->authorize('view', $automation);

        $automation->load(['triggers', 'steps.action', 'steps.conditions']);

        $timeline = [];
        $cumulativeDelay = 0;

        // Trigger event
        $timeline[] = [
            'type' => 'trigger',
            'time' => 0,
            'label' => $automation->triggers->first()->event_name ?? 'Unknown Event',
            'description' => 'Workflow triggered',
        ];

        // Steps
        foreach ($automation->steps as $step) {
            if ($step->delay > 0) {
                $cumulativeDelay += $step->delay;
            }

            $timeline[] = [
                'type' => 'step',
                'time' => $cumulativeDelay,
                'label' => $this->getStepLabel($step),
                'description' => $step->description ?? $this->getStepDescription($step),
                'action_type' => $step->action->type ?? 'unknown',
                'is_enabled' => $step->is_enabled,
                'has_conditions' => $step->conditions->isNotEmpty(),
            ];
        }

        return response()->json([
            'timeline' => $timeline,
            'total_duration' => $cumulativeDelay,
        ]);
    }

    /**
     * Generate Mermaid diagram syntax from collections
     */
    protected function generateMermaidGraph($triggers, $globalConditions, $steps): string
    {
        $lines = [];
        $lines[] = 'graph TD';

        // Start node
        $triggerEvent = $triggers->first()->event_name ?? 'Event';
        $triggerEvent = str_replace([':', '.'], [' - ', ' '], $triggerEvent);
        $lines[] = '    Start([Start - ' . $triggerEvent . '])';

        // Global conditions check
        if ($globalConditions->isNotEmpty()) {
            $lines[] = '    Start --> GlobalConditions{Global Conditions}';
            $lines[] = '    GlobalConditions -->|Pass| Step1';
            $lines[] = '    GlobalConditions -->|Fail| End([End])';
            $previousNode = 'Step1';
        } else {
            $lines[] = '    Start --> Step1';
            $previousNode = 'Step1';
        }

        // Steps
        $stepCount = $steps->count();
        foreach ($steps as $index => $step) {
            $stepNum = $index + 1;
            $nextStepNum = $stepNum + 1;
            $stepId = 'Step' . $stepNum;
            $nextStepId = 'Step' . $nextStepNum;

            $label = $this->getStepLabel($step);

            // Step node
            if ($step->is_enabled) {
                $lines[] = "    {$stepId}[{$label}]";
            } else {
                $lines[] = "    {$stepId}[{$label} - DISABLED]";
                $lines[] = "    style {$stepId} fill:#f0f0f0,stroke:#999,stroke-dasharray: 5 5";
            }

            // Delay indicator
            if ($step->delay > 0) {
                $delayLabel = $this->formatDelay($step->delay);
                $lines[] = "    {$stepId} -->|Wait {$delayLabel}| Delay{$stepNum}((Wait))";
                $previousNode = "Delay{$stepNum}";
            }

            // Step conditions
            if ($step->conditions->isNotEmpty()) {
                $conditionId = "Condition{$stepNum}";
                $lines[] = "    {$previousNode} --> {$conditionId}{Step Conditions}";

                if ($index < $stepCount - 1) {
                    $lines[] = "    {$conditionId} -->|Pass| {$nextStepId}";
                } else {
                    $lines[] = "    {$conditionId} -->|Pass| End";
                }

                $lines[] = "    {$conditionId} -->|Fail| Skip{$stepNum}[Skip]";
                $lines[] = "    Skip{$stepNum} --> " . ($index < $stepCount - 1 ? $nextStepId : 'End');

                $previousNode = $nextStepId;
            } else {
                // If it was a delay, link Delay -> NextStep
                // If it was just step, Step -> NextStep
                // But we already output Step[...]
                // The connection to current step was handled by previous iteration's end or global cond.
                // We need to link Current Step (or its Delay) -> Next Step

                // Wait... $previousNode is pointing to the *output* of the current flow, ready to connect to *next*.
                // Let's refine logic:
                // We just outputted Step Node. 
                // Link Prev -> StepId? No, that was done by prev iteration.

                // My logic in original file:
                // $lines[] = "    {$stepId} -->|Wait...| Delay..." set $previousNode = Delay
                // If no delay, $previousNode is still $stepId?
                // Let's look closer at original:
                // It was building connections *inside* the loop.

                // Correct logic:
                // 1. Link incoming ($previousNode) -> Current Step ($stepId)
                //    Wait, the original code didn't link incoming->current explicitly inside the loop for standard steps?
                //    Ah, lines 141: "{$previousNode} --> {$nextStepId}" -- this links CURRENT to NEXT.

                // Let's re-verify the "Delay" part.
                // Original: "{$stepId} --> Delay..." then $prev = Delay.
                // So now $prev is the end of this step chain.

                // So if no conditions:
                // We need to link $previousNode (which is now Step or Delay) --> Next Step

                // Let's redo the loop logic more carefully to match original
                if (!isset($hasLinkedInput)) {
                    // Initial link
                    // Global/Start -> Step1 is already handled above loop.
                }
            }

            // Re-evaluating the original loop logic for safely refactoring:
            // Original:
            // foreach...
            //   Node def: "StepX[...]"
            //   If delay: "StepX --> Delay", prev = Delay
            //   If conditions: "prev --> Cond", "Cond --> Next/End", "Cond --> Skip --> Next/End". prev = NextStepId
            //   Else (no cond): "prev --> Next/End". prev = NextStepId

            // Wait, if no delay, prev is still what?
            // "if ($step->delay > 0)"... prev = Delay.
            // But if NO delay, prev is... undefined? 
            // Ah, looking at original file lines 93/96: $previousNode = 'Step1' BEFORE loop.
            // But inside loop, it uses $previousNode to link to *Next*?
            // Line 127: "{$previousNode} --> {$conditionId}"
            // Line 141: "{$previousNode} --> {$nextStepId}"

            // Wait, $previousNode starts as Step1.
            // If Step 1 has delay: Step1 --> Delay. prev = Delay.
            // If Step 1 has cond: Delay --> Cond. 
            // If Step 1 has NO delay: Step1 --> Cond?
            // Yes.

            // So I need to set $previousNode = $stepId at start of loop? 
            // No, strictly speaking $stepId IS the start of the step.
            // The connection TO $stepId was made by the PREVIOUS iteration (or Start).
            // So inside the loop we assume connection TO $stepId exists.
            // We are building connection FROM $stepId (or Delay) TO Next.

            // So:
            // 1. Define Step Node.
            // 2. Current "tail" is $stepId.
            $currentTail = $stepId;

            // 3. If Delay: $stepId --> Delay. $currentTail = Delay.
            if ($step->delay > 0) {
                $delayLabel = $this->formatDelay($step->delay);
                $lines[] = "    {$stepId} -->|Wait {$delayLabel}| Delay{$stepNum}((Wait))";
                $currentTail = "Delay{$stepNum}";
            }

            // 4. If Condition:
            if ($step->conditions->isNotEmpty()) {
                $conditionId = "Condition{$stepNum}";
                $lines[] = "    {$currentTail} --> {$conditionId}{Step Conditions}";

                if ($index < $stepCount - 1) {
                    $lines[] = "    {$conditionId} -->|Pass| {$nextStepId}";
                    $lines[] = "    {$conditionId} -->|Fail| Skip{$stepNum}[Skip]";
                    $lines[] = "    Skip{$stepNum} --> {$nextStepId}";
                } else {
                    $lines[] = "    {$conditionId} -->|Pass| End";
                    $lines[] = "    {$conditionId} -->|Fail| Skip{$stepNum}[Skip]";
                    $lines[] = "    Skip{$stepNum} --> End";
                }
            } else {
                // No conditions. Link Tail -> Next
                if ($index < $stepCount - 1) {
                    $lines[] = "    {$currentTail} --> {$nextStepId}";
                } else {
                    $lines[] = "    {$currentTail} --> End";
                }
            }
        }

        // End node styling
        if (!str_contains(end($lines), 'End')) {
            // Redundant handled by else blocks above, but ok
        }

        // Styling
        $lines[] = '    style Start fill:#e1f5e1,stroke:#4caf50';
        $lines[] = '    style End fill:#ffe1e1,stroke:#f44336';

        return implode("\n", $lines);
    }

    /**
     * Get a readable label for a step
     */
    protected function getStepLabel(object $step): string
    {
        $actionType = $step->action->type ?? 'Action';

        $labels = [
            'email' => 'Send Email',
            'webhook' => 'Webhook',
            'notification' => 'Notification',
            'status_update' => 'Update Status',
        ];

        return $labels[$actionType] ?? ucfirst($actionType);
    }

    /**
     * Get a description for a step
     */
    protected function getStepDescription(object $step): string
    {
        $action = $step->action;
        if (!$action) {
            return 'No action configured';
        }

        $config = (array) $action->config;

        switch ($action->type) {
            case 'email':
                return 'To: ' . ($config['to'] ?? 'Unknown');
            case 'webhook':
                return 'URL: ' . ($config['url'] ?? 'Unknown');
            case 'notification':
                return $config['message'] ?? 'Send notification';
            case 'status_update':
                return 'Set ' . ($config['field'] ?? 'field') . ' to ' . ($config['value'] ?? 'value');
            default:
                return ucfirst($action->type);
        }
    }

    /**
     * Format delay in human-readable format
     */
    protected function formatDelay(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . 's';
        } elseif ($seconds < 3600) {
            return round($seconds / 60) . 'm';
        } elseif ($seconds < 86400) {
            return round($seconds / 3600) . 'h';
        } else {
            return round($seconds / 86400) . 'd';
        }
    }
}

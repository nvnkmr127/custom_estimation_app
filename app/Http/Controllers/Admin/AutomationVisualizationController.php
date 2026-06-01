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
        } else {
            $lines[] = '    Start --> Step1';
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

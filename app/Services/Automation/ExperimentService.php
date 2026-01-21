<?php

namespace App\Services\Automation;

use App\Models\Automation;
use Illuminate\Support\Facades\DB;

class ExperimentService
{
    /**
     * Dispatch an event to an experiment variant (if valid).
     * Returns the selected Automation model or null if no active experiment.
     */
    public function dispatch(string $event, object $payload): ?Automation
    {
        // Check for active experiment for this event
        $experiment = DB::table('automation_experiments')
            ->where('trigger_event', $event)
            ->where('status', 'running')
            ->first();

        if (!$experiment) {
            return null;
        }

        // Get variants
        $variants = DB::table('automation_experiment_variants')
            ->where('experiment_id', $experiment->id)
            ->get();

        if ($variants->isEmpty()) {
            return null;
        }

        // Deterministic Selection
        $entityId = $this->extractEntityId($payload);

        // Hash the ID + Experiment ID to get a consistent number 0-99
        $hash = crc32((string) ($entityId . $experiment->id)) % 100;

        $currentWeight = 0;
        foreach ($variants as $variant) {
            $currentWeight += $variant->weight;
            if ($hash < $currentWeight) {
                DB::table('automation_experiment_variants')
                    ->where('id', $variant->id)
                    ->increment('executions_count');

                return Automation::find($variant->automation_id);
            }
        }

        return Automation::find($variants->first()->automation_id);
    }

    /**
     * Record a conversion if the event matches an experiment's goal.
     */
    public function recordConversion(string $event, object $payload): void
    {
        // Find experiments where this event is the GOAL
        $experiments = DB::table('automation_experiments')
            ->where('goal_event', $event)
            ->where('status', 'running')
            ->get();

        if ($experiments->isEmpty()) {
            return;
        }

        $entityId = $this->extractEntityId($payload);

        foreach ($experiments as $experiment) {
            // Re-run dispatch logic to find which variant this user belongs to
            $hash = crc32((string) ($entityId . $experiment->id)) % 100;

            // Fetch variants again to determine boundaries
            $variants = DB::table('automation_experiment_variants')
                ->where('experiment_id', $experiment->id)
                ->get();

            $currentWeight = 0;
            foreach ($variants as $variant) {
                $currentWeight += $variant->weight;
                if ($hash < $currentWeight) {
                    // Assign conversion to this variant
                    DB::table('automation_experiment_variants')
                        ->where('id', $variant->id)
                        ->increment('conversions_count');
                    break;
                }
            }
        }
    }

    /**
     * Calculate statistical significance between two variants using Z-test.
     * Returns percent confidence (0.0 to 100.0) that Test beats Control.
     */
    public function calculateSignificance(object $control, object $test): float
    {
        $n1 = $control->executions_count;
        $x1 = $control->conversions_count;
        $n2 = $test->executions_count;
        $x2 = $test->conversions_count;

        if ($n1 == 0 || $n2 == 0)
            return 0.0;

        $p1 = $x1 / $n1; // Conversion Rate Control
        $p2 = $x2 / $n2; // Conversion Rate Test

        // If test is worse or equal, confidence it beats control is low
        if ($p2 <= $p1)
            return 0.0;

        $p = ($x1 + $x2) / ($n1 + $n2); // Pooled proportion

        if ($p == 0 || $p == 1)
            return 0.0;

        $se = sqrt($p * (1 - $p) * ((1 / $n1) + (1 / $n2))); // Standard Error
        $z = ($p2 - $p1) / $se;

        return $this->cumulativeNormalDistribution($z) * 100;
    }

    /**
     * Approximate Cumulative Normal Distribution Function.
     */
    private function cumulativeNormalDistribution($x)
    {
        // Constants (Abramowitz and Stegun)
        $b1 = 0.319381530;
        $b2 = -0.356563782;
        $b3 = 1.781477937;
        $b4 = -1.821255978;
        $b5 = 1.330274429;
        $p = 0.2316419;
        $c = 0.39894228;

        if ($x >= 0.0) {
            $t = 1.0 / (1.0 + $p * $x);
            return (1.0 - $c * exp(-$x * $x / 2.0) * $t *
                ($t * ($t * ($t * ($t * $b5 + $b4) + $b3) + $b2) + $b1));
        } else {
            $t = 1.0 / (1.0 - $p * $x);
            return ($c * exp(-$x * $x / 2.0) * $t *
                ($t * ($t * ($t * ($t * $b5 + $b4) + $b3) + $b2) + $b1));
        }
    }

    private function extractEntityId($payload)
    {
        $id = $payload->id ?? null;
        if (!$id && isset($payload->data) && is_array($payload->data)) {
            $id = $payload->data['id'] ?? null;
        }
        if (!$id && isset($payload->object) && is_array($payload->object)) {
            $id = $payload->object['id'] ?? null;
        }
        return $id ?? uniqid();
    }
}

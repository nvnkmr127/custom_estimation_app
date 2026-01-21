<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Automation;
use App\Services\Automation\ExperimentService;
use App\Core\Events\DomainEvent;

class ExperimentSeeder extends Seeder
{
    public function run()
    {
        // 1. Create Two Variants
        $variantA = Automation::create([
            'name' => 'Variant A - Standard',
            'is_active' => true,
            'is_current_version' => true,
            'version' => 1,
            'created_by' => 1,
            'last_edited_by' => 1,
            'settings' => [],
        ]);

        $variantB = Automation::create([
            'name' => 'Variant B - Test',
            'is_active' => true,
            'is_current_version' => true,
            'version' => 1,
            'created_by' => 1,
            'last_edited_by' => 1,
            'settings' => [],
        ]);

        // 2. Create Experiment
        $experimentId = DB::table('automation_experiments')->insertGetId([
            'name' => 'Subject Line Test',
            'trigger_event' => 'test_event',
            'status' => 'running',
            'started_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('automation_experiment_variants')->insert([
            'experiment_id' => $experimentId,
            'automation_id' => $variantA->id,
            'weight' => 50,
        ]);

        DB::table('automation_experiment_variants')->insert([
            'experiment_id' => $experimentId,
            'automation_id' => $variantB->id,
            'weight' => 50,
        ]);

        // 3. Simulate Traffic
        $service = new ExperimentService();
        $counts = ['A' => 0, 'B' => 0];

        echo "Simulating 100 events...\n";
        for ($i = 0; $i < 100; $i++) {
            $payload = (object) ['id' => $i];
            $result = $service->dispatch('test_event', $payload);

            if ($result->id === $variantA->id)
                $counts['A']++;
            else
                $counts['B']++;
        }

        echo "Results: Variant A: {$counts['A']}, Variant B: {$counts['B']}\n";

        if (abs($counts['A'] - 50) > 10) {
            echo "WARNING: Distribution seems skewed!\n";
        } else {
            echo "Distribution looks correct (~50/50)\n";
        }
    }
}

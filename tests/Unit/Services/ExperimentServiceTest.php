<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Automation\ExperimentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Models\Automation;

class ExperimentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ExperimentService();
    }

    /** @test */
    public function it_calculates_significance_correctly()
    {
        // Case 1: Equal performance (no difference)
        $control = (object) ['executions_count' => 100, 'conversions_count' => 10];
        $test = (object) ['executions_count' => 100, 'conversions_count' => 10];
        // Cannot be confident test is better
        $this->assertEquals(0.0, $this->service->calculateSignificance($control, $test));

        // Case 2: Test performs significantly BETTER
        // Control: 10% (100/1000), Test: 15% (150/1000)
        $control = (object) ['executions_count' => 1000, 'conversions_count' => 100];
        $test = (object) ['executions_count' => 1000, 'conversions_count' => 150];

        $confidence = $this->service->calculateSignificance($control, $test);
        $this->assertGreaterThan(99.0, $confidence); // Should be very confident (Z ~ 3.5)

        // Case 3: Test performs WORSE
        $control = (object) ['executions_count' => 100, 'conversions_count' => 20];
        $test = (object) ['executions_count' => 100, 'conversions_count' => 10];
        $this->assertEquals(0.0, $this->service->calculateSignificance($control, $test));
    }

    /** @test */
    public function it_dispatches_deterministically()
    {
        // Setup Experiment
        $experimentId = DB::table('automation_experiments')->insertGetId([
            'name' => 'Test',
            'trigger_event' => 'test.event',
            'status' => 'running',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $autoA = Automation::create([
            'name' => 'A',
            'is_active' => true,
            'is_current_version' => true,
            'version' => 1
        ]);
        $autoB = Automation::create([
            'name' => 'B',
            'is_active' => true,
            'is_current_version' => true,
            'version' => 1
        ]);

        DB::table('automation_experiment_variants')->insert([
            ['experiment_id' => $experimentId, 'automation_id' => $autoA->id, 'weight' => 50],
            ['experiment_id' => $experimentId, 'automation_id' => $autoB->id, 'weight' => 50],
        ]);

        $payload = (object) ['id' => 12345];

        // First call
        $result1 = $this->service->dispatch('test.event', $payload);

        // Second call same ID
        $result2 = $this->service->dispatch('test.event', $payload);

        $this->assertEquals($result1->id, $result2->id, "Dispatch should return same variant for same ID");
    }

    /** @test */
    public function it_records_conversion()
    {
        // Setup Experiment with Goal
        $experimentId = DB::table('automation_experiments')->insertGetId([
            'name' => 'Conversion Test',
            'trigger_event' => 'trigger.event',
            'goal_event' => 'goal.event',
            'status' => 'running',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $autoA = Automation::create([
            'name' => 'A',
            'is_active' => true,
            'is_current_version' => true,
            'version' => 1
        ]);
        $autoB = Automation::create([
            'name' => 'B',
            'is_active' => true,
            'is_current_version' => true,
            'version' => 1
        ]);

        // Use distinct weights to force a specific outcome for a specific ID if we wanted, 
        // but for now we just want to ensure it increments SOME variant.
        $varA = DB::table('automation_experiment_variants')->insertGetId([
            'experiment_id' => $experimentId,
            'automation_id' => $autoA->id,
            'weight' => 50,
            'executions_count' => 0,
            'conversions_count' => 0
        ]);
        $varB = DB::table('automation_experiment_variants')->insertGetId([
            'experiment_id' => $experimentId,
            'automation_id' => $autoB->id,
            'weight' => 50,
            'executions_count' => 0,
            'conversions_count' => 0
        ]);

        $payload = (object) ['id' => 999];

        // 1. Dispatch (to ensure we know which one it picked, though service recalculates)
        $this->service->dispatch('trigger.event', $payload);

        // 2. Record Conversion
        $this->service->recordConversion('goal.event', $payload);

        // Check one of them increased
        $countA = DB::table('automation_experiment_variants')->where('id', $varA)->value('conversions_count');
        $countB = DB::table('automation_experiment_variants')->where('id', $varB)->value('conversions_count');

        $this->assertEquals(1, $countA + $countB, "Exactly one conversion should be recorded");
    }
}

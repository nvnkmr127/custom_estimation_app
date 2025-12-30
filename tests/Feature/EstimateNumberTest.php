<?php

namespace Tests\Feature;

use App\Models\Estimate;
use App\Services\EstimateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class EstimateNumberTest extends TestCase
{
    use RefreshDatabase;

    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EstimateService();
    }

    /** @test */
    public function it_generates_first_number_for_year()
    {
        $year = date('Y');
        $number = $this->service->generateNextNumber();

        $this->assertEquals("EST-{$year}-001", $number);
    }

    /** @test */
    public function it_increments_sequence_correctly()
    {
        $year = date('Y');

        // Create an existing estimate
        Estimate::factory()->create([
            'estimate_number' => "EST-{$year}-001",
        ]);

        $number = $this->service->generateNextNumber();
        $this->assertEquals("EST-{$year}-002", $number);
    }

    /** @test */
    public function it_handles_padding_correctly()
    {
        $year = date('Y');

        Estimate::factory()->create([
            'estimate_number' => "EST-{$year}-009",
        ]);

        $number = $this->service->generateNextNumber();
        $this->assertEquals("EST-{$year}-010", $number);
    }

    /** @test */
    public function it_handles_long_sequences()
    {
        $year = date('Y');

        Estimate::factory()->create([
            'estimate_number' => "EST-{$year}-099",
        ]);

        $number = $this->service->generateNextNumber();
        $this->assertEquals("EST-{$year}-100", $number);
    }

    /** @test */
    public function it_ignores_other_years()
    {
        $currentYear = date('Y');
        $lastYear = $currentYear - 1;

        Estimate::factory()->create([
            'estimate_number' => "EST-{$lastYear}-999",
        ]);

        $number = $this->service->generateNextNumber();
        $this->assertEquals("EST-{$currentYear}-001", $number);
    }

    /** @test */
    public function it_finds_correct_last_number_even_with_strings()
    {
        $year = date('Y');

        // Simulate messy data where 10 comes after 9 alphabetically
        Estimate::factory()->create(['estimate_number' => "EST-{$year}-009"]);
        Estimate::factory()->create(['estimate_number' => "EST-{$year}-010"]);

        // 10 should be the last one
        $number = $this->service->generateNextNumber();
        $this->assertEquals("EST-{$year}-011", $number);
    }
}

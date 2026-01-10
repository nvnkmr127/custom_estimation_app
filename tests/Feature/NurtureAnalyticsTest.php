<?php

namespace Tests\Feature;

use App\Models\Estimate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NurtureAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_calculates_nurture_analytics_correctly()
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        // 1. Nurtured but NOT accepted
        Estimate::factory()->create([
            'status' => 'sent',
            'last_nurtured_at' => now(),
            'grand_total' => 1000
        ]);

        // 2. Nurtured AND Accepted (Win)
        Estimate::factory()->create([
            'status' => 'accepted',
            'last_nurtured_at' => now(),
            'grand_total' => 2000
        ]);

        // 3. Accepted but NEVER nurtured (Organic Win)
        Estimate::factory()->create([
            'status' => 'accepted',
            'last_nurtured_at' => null,
            'grand_total' => 5000
        ]);

        // 4. Just Sent (No nurture)
        Estimate::factory()->create([
            'status' => 'sent',
            'last_nurtured_at' => null,
            'grand_total' => 500
        ]);

        // Expected Stats:
        // Total Nurtured: 2 (Case 1 + Case 2)
        // Nurtured Wins: 1 (Case 2)
        // Revenue: 2000 (Case 2)
        // Conversion Rate: 1 / 2 = 50%

        $response = $this->actingAs($admin)->get(route('settings.nurture'));

        $response->assertStatus(200);
        $response->assertViewHas('analytics');

        $analytics = $response->viewData('analytics');

        $this->assertEquals(2, $analytics['totalNurtured']);
        $this->assertEquals(1, $analytics['nurturedWins']);
        $this->assertEquals(2000, $analytics['generatedRevenue']);
        $this->assertEquals(50.0, $analytics['conversionRate']);

        // Check if values appear in view
        $response->assertSee('2,000.00'); // Revenue
        $response->assertSee('50%'); // Conversion Rate
    }
}

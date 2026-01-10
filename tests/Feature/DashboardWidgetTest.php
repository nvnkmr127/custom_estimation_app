<?php

namespace Tests\Feature;

use App\Models\Estimate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardWidgetTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_displays_hot_leads_on_dashboard()
    {
        // 1. Arrange: Create user and estimates
        $user = User::factory()->create();

        // Hot Lead: Sent, High Engagement
        $hotEstimate = Estimate::factory()->create([
            'status' => 'sent',
            'engagement_score' => 5,
            'last_viewed_at' => now(),
            'estimate_number' => 'EST-HOT-001',
        ]);

        // Cold Lead: Sent, No Engagement
        $coldEstimate = Estimate::factory()->create([
            'status' => 'sent',
            'engagement_score' => 0,
            'estimate_number' => 'EST-COLD-001',
        ]);

        // Draft (Should not show)
        $draftEstimate = Estimate::factory()->create([
            'status' => 'draft',
            'engagement_score' => 10, // Even with score, drafts shouldn't be "hot leads" typically? logic says status='sent'
        ]);

        // 2. Act: Visit Dashboard
        $response = $this->actingAs($user)->get(route('dashboard'));

        // 3. Assert
        $response->assertStatus(200);

        // Check for widget presence
        $response->assertSee('Hot Leads');
        $response->assertSee('EST-HOT-001');

        // Assert Cold estimate is NOT in the Hot Leads section (Checking strictly might be hard with just text assert, but we can check if it appears in general)
        // Ideally we check that hot count is 1.
        $response->assertSee('1 Alert');

        // Cold estimate might be in "Recent Estimates", so we can't assertDontSee('EST-COLD-001') globally.
    }
}

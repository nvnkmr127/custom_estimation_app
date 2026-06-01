<?php

namespace Tests\Feature;

use App\Models\Estimate;
use App\Models\Client;
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
        $hotClient = Client::factory()->create(['name' => 'Hot Client']);
        $hotEstimate = Estimate::factory()->create([
            'created_by' => $user->id,
            'client_id' => $hotClient->id,
            'status' => 'sent',
            'estimate_status' => Estimate::EST_STATUS_SENT,
            'engagement_score' => 5,
            'last_viewed_at' => now(),
            'estimate_number' => 'EST-HOT-001',
        ]);

        // Cold Lead: Sent, No Engagement
        $coldClient = Client::factory()->create(['name' => 'Cold Client']);
        $coldEstimate = Estimate::factory()->create([
            'created_by' => User::factory()->create()->id,
            'client_id' => $coldClient->id,
            'status' => 'sent',
            'estimate_status' => Estimate::EST_STATUS_SENT,
            'engagement_score' => 0,
            'estimate_number' => 'EST-COLD-001',
        ]);

        // Draft (Should not show)
        $draftEstimate = Estimate::factory()->create([
            'created_by' => $user->id,
            'status' => 'draft',
            'estimate_status' => Estimate::EST_STATUS_DRAFT,
            'engagement_score' => 10,
        ]);

        // 2. Act: Visit Dashboard
        \Illuminate\Support\Facades\Cache::flush();
        $response = $this->actingAs($user)->get(route('dashboard'));

        // 3. Assert
        $response->assertStatus(200);

        // Check for widget presence
        $response->assertSee('Hot Leads');
        $response->assertSee('Hot Client');
        $response->assertSee('Score: 5');

        $response->assertDontSee('Cold Client');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Estimate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_download_logs_analytics()
    {
        $user = User::factory()->create();
        $estimate = Estimate::factory()->create();

        $this->actingAs($user)
            ->get(route('estimates.pdf', $estimate));

        $this->assertDatabaseHas('estimate_analytics', [
            'estimate_id' => $estimate->id,
            'action' => 'download',
        ]);
    }

    public function test_portal_view_logs_analytics()
    {
        $estimate = Estimate::factory()->create();
        $url = URL::signedRoute('portal.show', $estimate);

        $this->get($url);

        $this->assertDatabaseHas('estimate_analytics', [
            'estimate_id' => $estimate->id,
            'action' => 'view',
        ]);
    }
}

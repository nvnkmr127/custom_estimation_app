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
        $user = User::factory()->create(['role' => 'super_admin']);
        $template = \App\Models\PdfTemplate::factory()->create(['is_active' => true, 'is_default' => true]);
        $estimate = Estimate::factory()->create(['pdf_template_id' => $template->id]);

        $this->actingAs($user)
            ->get(route('estimates.pdf', $estimate));

        $this->assertDatabaseHas('estimate_analytics', [
            'estimate_id' => $estimate->id,
            'action' => 'download',
        ]);
    }

    public function test_portal_view_logs_analytics()
    {
        $client = \App\Models\Client::factory()->create();
        $estimate = Estimate::factory()->create([
            'client_id' => $client->id,
            'status' => 'sent',
            'estimate_status' => Estimate::EST_STATUS_SENT,
            'client_status' => Estimate::CLT_STATUS_SENT,
            'is_current_version' => true,
            'expires_at' => now()->addDays(7),
        ]);
        $url = URL::signedRoute('portal.show', $estimate);

        $this->get($url)->assertOk();

        $this->assertDatabaseHas('estimate_analytics', [
            'estimate_id' => $estimate->id,
            'action' => 'view',
        ]);
    }
}

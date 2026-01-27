<?php

namespace Tests\Feature;

use App\Models\Estimate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PortalSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_route_access()
    {
        $estimate = Estimate::factory()->create([
            'status' => 'sent',
            'estimate_number' => 'EST-001'
        ]);

        // 1. Valid Signature
        $url = URL::temporarySignedRoute('portal.show', now()->addHour(), ['estimate' => $estimate->id]);
        $response = $this->get($url);
        $response->assertStatus(200);

        // 2. Invalid Signature (Modified ID)
        $invalidUrl = str_replace($estimate->id, $estimate->id + 1, $url);
        $response = $this->get($invalidUrl);
        $response->assertStatus(403);

        // 3. Expired Signature
        $expiredUrl = URL::temporarySignedRoute('portal.show', now()->subHour(), ['estimate' => $estimate->id]);
        $response = $this->get($expiredUrl);
        $response->assertStatus(403);
    }

    public function test_status_access_control()
    {
        // Draft Check
        $draft = Estimate::factory()->create(['status' => 'draft']);
        $url = URL::temporarySignedRoute('portal.show', now()->addHour(), ['estimate' => $draft->id]);
        $response = $this->get($url);
        $response->assertStatus(403); // Should allow signed but controller blocks status

        // Accepted Check
        $accepted = Estimate::factory()->create(['status' => 'accepted']);
        $url2 = URL::temporarySignedRoute('portal.show', now()->addHour(), ['estimate' => $accepted->id]);
        $response = $this->get($url2);
        $response->assertStatus(200);
    }

    public function test_cannot_replay_accept()
    {
        $estimate = Estimate::factory()->create(['status' => 'sent']);
        $url = URL::temporarySignedRoute('portal.accept', now()->addHour(), ['estimate' => $estimate->id]);

        // First Accept
        $response = $this->post($url, ['signature' => 'data:image/png...']);
        $response->assertRedirect();
        $this->assertEquals('accepted', $estimate->fresh()->status);

        // Second Accept - Should fail or handle gracefully (idempotent)
        // Controller returns redirect back with info message
        $response = $this->post($url, ['signature' => 'data:image/png...']);
        $response->assertRedirect();
        // ensure it didn't do anything weird (like log another event ideally, but basic check is status)
        $this->assertEquals('accepted', $estimate->fresh()->status);
    }
}

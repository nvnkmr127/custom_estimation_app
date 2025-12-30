<?php

namespace Tests\Feature;

use App\Models\Estimate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_rejects_unauthorized_requests()
    {
        $response = $this->postJson('/webhooks/perfex', [
            'event_type' => 'proposal_accepted',
            'id' => 1,
        ]);

        $response->assertStatus(403);
    }

    public function test_webhook_accepts_authorized_requests()
    {
        config(['services.perfex.webhook_secret' => 'test-secret']);

        // Create a client
        $client = \App\Models\Client::create(['name' => 'Test Client', 'email' => 'test@example.com']);

        // Create a dummy estimate
        $estimate = Estimate::create([
            'title' => 'Test Estimate',
            'estimate_number' => 'EST-100',
            'status' => 'sent',
            'perfex_proposal_id' => 123,
            'client_id' => $client->id,
            'estimate_date' => now(),
        ]);

        $response = $this->withHeaders([
            'X-Perfex-Token' => 'test-secret',
        ])->postJson('/webhooks/perfex', [
            'event_type' => 'proposal_accepted',
            'id' => 123,
        ]);

        $response->assertStatus(200);

        $this->assertEquals('accepted', $estimate->fresh()->status);
    }
}

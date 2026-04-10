<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Estimate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EstimateSigningTest extends TestCase
{
    use RefreshDatabase;

    public function test_captures_signer_info_on_acceptance()
    {
        // Setup
        $client = Client::factory()->create();
        $estimate = Estimate::factory()->create([
            'client_id' => $client->id,
            'status' => 'sent',
            'estimate_status' => Estimate::EST_STATUS_SENT,
            'client_status' => Estimate::CLT_STATUS_SENT,
            'is_current_version' => true,
            'expires_at' => now()->addDays(7),
        ]);

        // Generate Signed URL
        $url = URL::signedRoute('portal.accept', $estimate);

        // Mock Request with specific User Agent and IP
        $response = $this->withHeaders([
            'User-Agent' => 'TestBrowser/1.0',
        ])->post($url, [
            'signature' => 'data:image/png;base64,testsignature',
        ]);

        $response->assertSessionHas('success');

        $estimate->refresh();

        $this->assertEquals(Estimate::CLT_STATUS_ACCEPTED, $estimate->client_status);
        $this->assertEquals(Estimate::EST_STATUS_ACCEPTED, $estimate->estimate_status);
        $this->assertEquals('TestBrowser/1.0', $estimate->signer_agent);
        $this->assertNotNull($estimate->signer_ip);
        // Note: Location might be null in tests due to localhost/mocking, which is fine
        // If we want to test location we'd need to mock file_get_contents or the IP
    }
}

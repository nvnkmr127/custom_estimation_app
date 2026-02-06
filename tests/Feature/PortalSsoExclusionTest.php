<?php

namespace Tests\Feature;

use App\Models\Estimate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PortalSsoExclusionTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_portal_route_does_not_redirect_to_sso(): void
    {
        Config::set('sso.enabled', true);
        Config::set('sso.auth_core_url', 'https://auth.example.com');

        $estimate = Estimate::factory()->create(['status' => Estimate::STATUS_SENT]);

        // Generate a valid signed URL
        $url = URL::signedRoute('portal.show', ['estimate' => $estimate->id]);

        // Access the signed URL as a guest
        $response = $this->get($url);

        // Assert it does NOT redirect to SSO (it should show the portal page, status 200)
        $response->assertStatus(200);
        $response->assertViewIs('portal.estimates.show');
        $this->assertGuest();
    }

    public function test_unsigned_portal_route_returns_error_instead_of_sso_redirect(): void
    {
        Config::set('sso.enabled', true);
        Config::set('sso.auth_core_url', 'https://auth.example.com');

        $estimate = Estimate::factory()->create(['status' => Estimate::STATUS_SENT]);

        // Access WITHOUT a signature
        $response = $this->get(route('portal.show', ['estimate' => $estimate->id]));

        // Should be 403 Forbidden (from signed middleware), NOT a redirect to SSO
        $response->assertStatus(403);
    }
}

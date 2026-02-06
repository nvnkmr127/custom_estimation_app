<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_clears_local_session(): void
    {
        Config::set('sso.enabled', false);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_logout_redirects_to_auth_core_when_centralized_enabled(): void
    {
        Config::set('sso.enabled', true);
        Config::set('sso.centralized_logout', true);
        Config::set('sso.auth_core_url', 'https://auth.example.com');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $expectedUrl = 'https://auth.example.com/logout?redirect=' . urlencode(url('/'));
        $response->assertRedirect($expectedUrl);
        $this->assertGuest();
    }

    public function test_logout_supports_local_only_mode(): void
    {
        Config::set('sso.enabled', true);
        Config::set('sso.centralized_logout', true);
        Config::set('sso.auth_core_url', 'https://auth.example.com');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout', ['local' => 1]);

        $response->assertRedirect('/');
        $this->assertGuest();
    }
}

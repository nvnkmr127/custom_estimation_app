<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class UnauthenticatedRedirectTest extends TestCase
{
    public function test_unauthenticated_user_is_redirected_to_sso_login_when_enabled(): void
    {
        Config::set('sso.enabled', true);
        Config::set('sso.auth_core_url', 'https://auth.example.com');

        $response = $this->get('/dashboard');

        $expectedUrl = 'https://auth.example.com/login?redirect=' . urlencode(url('/dashboard'));
        $response->assertRedirect($expectedUrl);
    }

    public function test_unauthenticated_user_is_redirected_to_local_login_when_sso_disabled(): void
    {
        Config::set('sso.enabled', false);

        $response = $this->get('/dashboard');

        $response->assertRedirect(route('login'));
    }
}

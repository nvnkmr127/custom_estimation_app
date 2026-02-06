<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class SsoCallbackTest extends TestCase
{
    use RefreshDatabase;

    protected string $privateKey = '';
    protected string $publicKey = '';

    protected function setUp(): void
    {
        parent::setUp();

        // Generate a temporary RS256 key pair
        $res = openssl_pkey_new([
            "digest_alg" => "sha256",
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ]);

        openssl_pkey_export($res, $this->privateKey);
        $details = openssl_pkey_get_details($res);
        $this->publicKey = $details['key'];

        Config::set('sso.enabled', true);
        Config::set('sso.public_key', $this->publicKey);
        Config::set('sso.jwt_audience', 'http://localhost:8000');
        Config::set('sso.auth_core_url', 'auth-core');
    }

    public function test_sso_callback_authenticates_and_creates_new_user(): void
    {
        $payload = [
            'iss' => 'auth-core',
            'aud' => 'http://localhost:8000',
            'sub' => 'sso-123',
            'jti' => 'unique-jti-123',
            'email' => 'newuser@example.com',
            'name' => 'SSO User',
            'iat' => time(),
            'exp' => time() + 3600
        ];

        $token = JWT::encode($payload, $this->privateKey, 'RS256');

        $response = $this->get('/sso/callback?token=' . $token);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $user = User::where('email', 'newuser@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('newuser@example.com', $user->email);
        $this->assertEquals('auth_core', $user->provider_name);
        $this->assertEquals('sso-123', $user->provider_id);
        $this->assertEquals('sso', $user->source);
        $this->assertEquals('estimator', $user->role); // Default role
    }

    public function test_sso_callback_maps_role_correctly(): void
    {
        $payload = [
            'iss' => 'auth-core',
            'aud' => 'http://localhost:8000',
            'sub' => 'sso-role-test',
            'jti' => 'unique-jti-role',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'iat' => time(),
            'exp' => time() + 3600
        ];

        $token = JWT::encode($payload, $this->privateKey, 'RS256');

        $response = $this->get('/sso/callback?token=' . $token);

        $response->assertRedirect(route('dashboard'));

        $user = User::where('email', 'admin@example.com')->first();
        $this->assertEquals('super_admin', $user->role); // Mapped from 'admin'
    }

    public function test_sso_callback_falls_back_to_default_role_if_unmapped(): void
    {
        $payload = [
            'iss' => 'auth-core',
            'aud' => 'http://localhost:8000',
            'sub' => 'sso-unmapped-test',
            'jti' => 'unique-jti-unmapped',
            'email' => 'guest@example.com',
            'role' => 'unknown_role',
            'iat' => time(),
            'exp' => time() + 3600
        ];

        $token = JWT::encode($payload, $this->privateKey, 'RS256');

        $response = $this->get('/sso/callback?token=' . $token);

        $response->assertRedirect(route('dashboard'));

        $user = User::where('email', 'guest@example.com')->first();
        $this->assertEquals('estimator', $user->role); // Fallback to default
    }

    public function test_sso_callback_authenticates_existing_user(): void
    {
        $user = User::factory()->create([
            'email' => 'existing@example.com',
            'name' => 'Existing User'
        ]);

        $payload = [
            'iss' => 'auth-core',
            'aud' => 'http://localhost:8000',
            'sub' => 'sso-456',
            'jti' => 'unique-jti-456',
            'email' => 'existing@example.com',
            'iat' => time(),
            'exp' => time() + 3600
        ];

        $token = JWT::encode($payload, $this->privateKey, 'RS256');

        $response = $this->get('/sso/callback?token=' . $token);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        $user->refresh();
        $this->assertEquals('auth_core', $user->provider_name);
        $this->assertEquals('sso-456', $user->provider_id);
    }

    public function test_sso_callback_fails_with_invalid_token(): void
    {
        $response = $this->get('/sso/callback?token=invalid.token.here');

        $response->assertStatus(403);
        $this->assertGuest();
    }

    public function test_sso_callback_fails_with_invalid_audience(): void
    {
        $payload = [
            'iss' => 'auth-core',
            'aud' => 'wrong-audience',
            'sub' => 'sso-123',
            'jti' => 'unique-jti-123',
            'email' => 'user@example.com',
            'iat' => time(),
            'exp' => time() + 3600
        ];

        $token = JWT::encode($payload, $this->privateKey, 'RS256');

        $response = $this->get('/sso/callback?token=' . $token);

        $response->assertStatus(403);
        $this->assertGuest();
    }

    public function test_sso_callback_fails_with_invalid_issuer(): void
    {
        $payload = [
            'iss' => 'wrong-issuer',
            'aud' => 'http://localhost:8000',
            'sub' => 'sso-123',
            'jti' => 'unique-jti-123',
            'email' => 'user@example.com',
            'iat' => time(),
            'exp' => time() + 3600
        ];

        $token = JWT::encode($payload, $this->privateKey, 'RS256');

        $response = $this->get('/sso/callback?token=' . $token);

        $response->assertStatus(403);
        $this->assertGuest();
    }

    public function test_sso_callback_fails_with_missing_jti(): void
    {
        $payload = [
            'iss' => 'auth-core',
            'aud' => 'http://localhost:8000',
            'sub' => 'sso-123',
            'email' => 'user@example.com',
            'iat' => time(),
            'exp' => time() + 3600
        ];

        $token = JWT::encode($payload, $this->privateKey, 'RS256');

        $response = $this->get('/sso/callback?token=' . $token);

        $response->assertStatus(403);
        $this->assertGuest();
    }

    public function test_sso_callback_rejects_replayed_jti(): void
    {
        $payload = [
            'iss' => 'auth-core',
            'aud' => 'http://localhost:8000',
            'sub' => 'sso-123',
            'jti' => 'replay-jti-test',
            'email' => 'user@example.com',
            'iat' => time(),
            'exp' => time() + 3600
        ];

        $token = JWT::encode($payload, $this->privateKey, 'RS256');

        // First use: Success
        $this->get('/sso/callback?token=' . $token)->assertRedirect(route('dashboard'));

        // Logout to simulate a fresh attempt with the same token
        \Illuminate\Support\Facades\Auth::logout();

        // Second use: Failure (Replay)
        $this->get('/sso/callback?token=' . $token)
            ->assertStatus(403);
    }
}

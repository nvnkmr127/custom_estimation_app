<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\DeviceToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_json_response_when_requested()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'role' => 'estimator',
        ]);

        $response = $this->postJson('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'user' => [
                'id',
                'name',
                'email',
            ],
            'token',
        ]);
    }

    public function test_register_device_token_successfully()
    {
        $user = User::factory()->create(['role' => 'estimator']);

        $response = $this->actingAs($user)->postJson('/devices/register', [
            'token' => 'ExponentPushToken[1234567890123456789012]',
            'platform' => 'android',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Device token registered successfully.',
        ]);

        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $user->id,
            'token' => 'ExponentPushToken[1234567890123456789012]',
            'platform' => 'android',
        ]);
    }

    public function test_deregister_device_token_successfully()
    {
        $user = User::factory()->create(['role' => 'estimator']);
        $token = DeviceToken::create([
            'user_id' => $user->id,
            'token' => 'ExponentPushToken[1234567890123456789012]',
            'platform' => 'android',
        ]);

        $response = $this->actingAs($user)->postJson('/devices/deregister', [
            'token' => 'ExponentPushToken[1234567890123456789012]',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Device token deregistered successfully.',
        ]);

        $this->assertDatabaseMissing('device_tokens', [
            'token' => 'ExponentPushToken[1234567890123456789012]',
        ]);
    }
}

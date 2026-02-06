<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SsoUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_model_has_sso_columns(): void
    {
        $user = User::factory()->create([
            'provider_name' => 'google',
            'provider_id' => '123456789',
        ]);

        $this->assertEquals('google', $user->provider_name);
        $this->assertEquals('123456789', $user->provider_id);
    }

    public function test_user_model_allows_mass_assignment_of_sso_columns(): void
    {
        $user = User::create([
            'name' => 'SSO User',
            'email' => 'sso@example.com',
            'password' => bcrypt('password'),
            'provider_name' => 'github',
            'provider_id' => 'gh-123',
        ]);

        $this->assertEquals('github', $user->provider_name);
        $this->assertEquals('gh-123', $user->provider_id);
    }

    public function test_sso_columns_can_be_null(): void
    {
        $user = User::factory()->create([
            'provider_name' => null,
            'provider_id' => null,
        ]);

        $this->assertNull($user->provider_name);
        $this->assertNull($user->provider_id);
    }

    public function test_provider_name_and_id_must_be_unique_together(): void
    {
        User::factory()->create([
            'email' => 'user1@example.com',
            'provider_name' => 'google',
            'provider_id' => 'same-id',
        ]);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        User::factory()->create([
            'email' => 'user2@example.com',
            'provider_name' => 'google',
            'provider_id' => 'same-id',
        ]);
    }
}

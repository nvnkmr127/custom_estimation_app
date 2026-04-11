<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function estimator_admin_cannot_update_settings_without_manage_settings()
    {
        $user = User::factory()->create([
            'role' => 'estimator_admin',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('settings.update'), [])
            ->assertStatus(403);
    }
}


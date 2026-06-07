<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_api_portal()
    {
        $user = User::factory()->create(['role' => 'estimator_admin']);

        $response = $this->actingAs($user)->get(route('admin.api-portal'));

        $response->assertStatus(200);
        $response->assertSee('API Developer Portal');
        $response->assertSee('Active Bearer Token');
    }

    public function test_super_admin_can_access_api_portal()
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($user)->get(route('admin.api-portal'));

        $response->assertStatus(200);
        $response->assertSee('API Developer Portal');
    }

    public function test_estimator_cannot_access_api_portal()
    {
        $user = User::factory()->create(['role' => 'estimator']);

        $response = $this->actingAs($user)->get(route('admin.api-portal'));

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_api_portal()
    {
        $response = $this->get(route('admin.api-portal'));

        $response->assertRedirect('/login');
    }
}

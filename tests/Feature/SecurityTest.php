<?php

namespace Tests\Feature;

use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function non_admin_cannot_access_settings()
    {
        $user = User::factory()->create(['role' => 'estimator']);
        $this->actingAs($user);

        $response = $this->get(route('settings.edit'));
        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_access_settings()
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin);

        $response = $this->get(route('settings.edit'));
        $response->assertStatus(200);
    }

    /** @test */
    public function mass_assignment_is_blocked_for_protected_fields()
    {
        $category = ProductCategory::create([
            'name' => 'Test Category',
            'description' => 'Test Description',
        ]);

        // Attempting to mass assign a field that shouldn't be fillable if it existed,
        // but here we test our actual fillable fields.
        $this->assertEquals('Test Category', $category->name);

        // If we had a sensitive field like 'is_system' and it wasn't in fillable, it would be ignored.
        // Let's check a real one we just fixed.
        $clientData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'unknown_field' => 'should be ignored',
        ];

        $client = \App\Models\Client::create($clientData);
        $this->assertEquals('John Doe', $client->name);
        $this->assertArrayNotHasKey('unknown_field', $client->getAttributes());
    }

    /** @test */
    public function perfex_webhook_requires_token()
    {
        $response = $this->postJson('/webhooks/perfex', ['event_type' => 'proposal_accepted']);
        $response->assertStatus(403);
    }

    /** @test */
    public function guest_cannot_access_dashboard()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }
}

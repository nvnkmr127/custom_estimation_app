<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutomationTemplatesNavigationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function automation_templates_menu_does_not_link_to_json_endpoint()
    {
        $user = User::factory()->create([
            'role' => 'super_admin',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('open_templates=1');
        $response->assertDontSee('/automation/templates/list');
    }
}


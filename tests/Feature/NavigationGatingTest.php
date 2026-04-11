<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationGatingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function estimator_admin_does_not_see_super_admin_only_links()
    {
        $user = User::factory()->create([
            'role' => 'estimator_admin',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertDontSee('Backup & Restore');
        $response->assertDontSee('Inbound Catchers');
        $response->assertDontSee('Outbound Endpoints');
        $response->assertDontSee('Payload Explorer');
        $response->assertDontSee('Delivery Logs');
        $response->assertDontSee('Dead Letter Queue');
        $response->assertDontSee('Approval Flows');
        $response->assertDontSee('Approval Checklists');
        $response->assertDontSee('Revision Checklists');
    }
}


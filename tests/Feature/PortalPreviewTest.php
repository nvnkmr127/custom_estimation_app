<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Estimate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalPreviewTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function administrators_can_view_all_estimates_on_the_portal_preview_list()
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $salesperson = User::factory()->create(['role' => 'sales']);
        $otherUser = User::factory()->create(['role' => 'sales']);

        $clientA = Client::factory()->create(['name' => 'Sales Client']);
        $clientB = Client::factory()->create(['name' => 'Other Client']);

        $estimateA = Estimate::factory()->create([
            'created_by' => $salesperson->id,
            'client_id' => $clientA->id,
            'is_current_version' => true,
        ]);

        $estimateB = Estimate::factory()->create([
            'created_by' => $otherUser->id,
            'client_id' => $clientB->id,
            'is_current_version' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('portal.preview-list'));

        $response->assertStatus(200);
        $response->assertSee('Sales Client');
        $response->assertSee('Other Client');
    }

    /** @test */
    public function salespersons_can_only_view_estimates_they_are_associated_with()
    {
        $salesperson = User::factory()->create(['role' => 'sales']);
        $otherUser = User::factory()->create(['role' => 'sales']);

        $clientA = Client::factory()->create(['name' => 'My Sales Client']);
        $clientB = Client::factory()->create(['name' => 'Not My Client']);

        $estimateA = Estimate::factory()->create([
            'created_by' => $salesperson->id,
            'client_id' => $clientA->id,
            'is_current_version' => true,
        ]);

        $estimateB = Estimate::factory()->create([
            'created_by' => $otherUser->id,
            'client_id' => $clientB->id,
            'is_current_version' => true,
        ]);

        $response = $this->actingAs($salesperson)->get(route('portal.preview-list'));

        $response->assertStatus(200);
        $response->assertSee('My Sales Client');
        $response->assertDontSee('Not My Client');
    }
}

<?php

namespace Tests\Feature;

use App\Models\ApprovalChain;
use App\Models\Client;
use App\Models\Estimate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RoleBoundaryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function super_admin_can_manage_approval_chains()
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($admin)->get(route('approval-chains.index'));
        $response->assertStatus(200);

        $response = $this->actingAs($admin)->post(route('approval-chains.store'), [
            'name' => 'New Chain',
            'steps' => [
                ['user_id' => $admin->id, 'order' => 1]
            ]
        ]);
        $response->assertRedirect(route('approval-chains.index'));
    }

    /** @test */
    public function estimator_admin_cannot_manage_approval_chains()
    {
        $admin = User::factory()->create(['role' => 'estimator_admin']);

        $response = $this->actingAs($admin)->get(route('approval-chains.index'));
        $response->assertStatus(403);
    }

    /** @test */
    public function estimator_cannot_manage_approval_chains()
    {
        $user = User::factory()->create(['role' => 'estimator']);

        $response = $this->actingAs($user)->get(route('approval-chains.index'));
        $response->assertStatus(403);
    }

    /** @test */
    public function cannot_edit_sent_estimate()
    {
        $user = User::factory()->create(['role' => 'estimator']);
        $client = Client::factory()->create();
        $estimate = Estimate::factory()->create([
            'title' => 'Sent Estimate',
            'estimate_number' => 'EST-SENT',
            'client_id' => $client->id,
            'estimate_status' => Estimate::EST_STATUS_SENT,
            'client_status' => Estimate::CLT_STATUS_SENT,
            'is_current_version' => true,
            'expires_at' => now()->addDays(7),
            'currency' => 'USD',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);
        $response = $this->put(route('estimates.update', $estimate), [
            'title' => 'Hacked Title',
            'client_id' => $client->id,
            'estimate_date' => now(),
            'currency' => 'USD',
            'status' => Estimate::EST_STATUS_DRAFT,
            'discount_type' => 'fixed',
            'type' => 'standard',
            'items' => [
                [
                    'name' => 'Line Item',
                    'unit_price' => 100,
                    'quantity' => 1,
                    'unit_type' => 'nos',
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals('Sent Estimate', $estimate->fresh()->title);
        $this->assertDatabaseHas('estimates', [
            'parent_id' => $estimate->id,
            'title' => 'Hacked Title',
        ]);
    }

    /** @test */
    public function revert_to_draft_unlocks_editing()
    {
        $user = User::factory()->create(['role' => 'estimator']);
        $client = Client::factory()->create();
        $estimate = Estimate::factory()->create([
            'title' => 'Sent Estimate 2',
            'estimate_number' => 'EST-SENT-2',
            'client_id' => $client->id,
            'estimate_status' => Estimate::EST_STATUS_SENT,
            'client_status' => Estimate::CLT_STATUS_SENT,
            'is_current_version' => true,
            'expires_at' => now()->addDays(7),
            'currency' => 'USD',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);

        // 1. Revert to draft
        $response = $this->post(route('estimates.revert', $estimate));
        $response->assertRedirect();

        $this->assertEquals(Estimate::EST_STATUS_DRAFT, $estimate->fresh()->estimate_status);

        // 2. Now Edit
        $response = $this->put(route('estimates.update', $estimate), [
            'title' => 'Corrected Title',
            'client_id' => $client->id,
            'estimate_date' => now(),
            'currency' => 'USD',
            'status' => Estimate::EST_STATUS_DRAFT,
            'discount_type' => 'fixed',
            'type' => 'standard',
            'items' => [
                [
                    'name' => 'Line Item',
                    'unit_price' => 100,
                    'quantity' => 1,
                    'unit_type' => 'nos',
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertEquals('Corrected Title', $estimate->fresh()->title);
    }
}

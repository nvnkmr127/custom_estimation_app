<?php

namespace Tests\Feature;

use App\Models\ApprovalChain;
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
        $estimate = Estimate::create([
            'title' => 'Sent Estimate',
            'estimate_number' => 'EST-SENT',
            'client_id' => 1,
            'estimate_date' => now(),
            'currency' => 'USD',
            'status' => 'sent',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);
        $response = $this->put(route('estimates.update', $estimate), [
            'title' => 'Hacked Title',
            'client_id' => 1,
            'estimate_date' => now(),
            'currency' => 'USD',
            'status' => 'sent',
            'discount_type' => 'fixed',
        ]);

        // Should be forbidden by Policy
        $response->assertStatus(403);

        $this->assertEquals('Sent Estimate', $estimate->fresh()->title);
    }

    /** @test */
    public function revert_to_draft_unlocks_editing()
    {
        $user = User::factory()->create(['role' => 'estimator']);
        $estimate = Estimate::create([
            'title' => 'Sent Estimate 2',
            'estimate_number' => 'EST-SENT-2',
            'client_id' => 1,
            'estimate_date' => now(),
            'currency' => 'USD',
            'status' => 'sent',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);

        // 1. Revert to draft
        $response = $this->post(route('estimates.revert', $estimate));
        $response->assertRedirect();

        $this->assertEquals('draft', $estimate->fresh()->status);

        // 2. Now Edit
        $response = $this->put(route('estimates.update', $estimate), [
            'title' => 'Corrected Title',
            'client_id' => 1,
            'estimate_date' => now(),
            'currency' => 'USD',
            'status' => 'draft',
            'discount_type' => 'fixed',
        ]);

        $response->assertRedirect();
        $this->assertEquals('Corrected Title', $estimate->fresh()->title);
    }
}

<?php

namespace Tests\Feature;

use App\Models\ApprovalChain;
use App\Models\ApprovalChainStep;
use App\Models\Estimate;
use App\Models\EstimateApproval;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_parallel_approvals()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $chain = ApprovalChain::create(['name' => 'Parallel Chain', 'is_active' => true]);

        // Step 1: Parallel (User 1 and User 2)
        ApprovalChainStep::create(['approval_chain_id' => $chain->id, 'user_id' => $user1->id, 'order' => 1, 'role' => 'approver']);
        ApprovalChainStep::create(['approval_chain_id' => $chain->id, 'user_id' => $user2->id, 'order' => 1, 'role' => 'approver']);

        // Step 2: User 3
        ApprovalChainStep::create(['approval_chain_id' => $chain->id, 'user_id' => $user3->id, 'order' => 2, 'role' => 'approver']);

        $estimate = Estimate::factory()->create(['approval_chain_id' => $chain->id]);

        // Submit
        $estimate->submitForApproval();

        // Should have 2 pending approvals
        $this->assertEquals(2, $estimate->approvals()->where('status', 'pending')->count());

        // Approve 1
        $this->actingAs($user1);
        $this->post(route('estimates.approve', $estimate));

        // Still 1 pending (User 2), User 1 is approved. No new approvals for User 3 yet.
        $this->assertEquals(1, $estimate->approvals()->where('status', 'pending')->count());
        $this->assertEquals(1, $estimate->approvals()->where('status', 'approved')->count());
        $this->assertEquals('submitted', $estimate->fresh()->approval_status);

        // Approve 2
        $this->actingAs($user2);
        $this->post(route('estimates.approve', $estimate));

        // Now Step 1 is done. Step 2 (User 3) should be pending.
        $this->assertEquals(1, $estimate->approvals()->where('status', 'pending')->count());
        $this->assertEquals($user3->id, $estimate->approvals()->where('status', 'pending')->first()->user_id);

        // Approve 3
        $this->actingAs($user3);
        $this->post(route('estimates.approve', $estimate));

        // Fully approved
        $this->assertEquals('approved', $estimate->fresh()->approval_status);
    }

    public function test_timeout_rejection()
    {
        $user = User::factory()->create();
        $chain = ApprovalChain::create(['name' => 'Timeout Chain', 'is_active' => true]);

        ApprovalChainStep::create([
            'approval_chain_id' => $chain->id,
            'user_id' => $user->id,
            'order' => 1,
            'role' => 'approver',
            'timeout_hours' => 1,
            'timeout_action' => 'reject'
        ]);

        $estimate = Estimate::factory()->create(['approval_chain_id' => $chain->id]);
        $estimate->submitForApproval();

        // Fast forward 2 hours
        $this->travel(2)->hours();

        $this->artisan('approval:check-timeouts');

        $this->assertEquals('rejected', $estimate->fresh()->approval_status);
    }

    public function test_timeout_skip()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $chain = ApprovalChain::create(['name' => 'Timeout Skip Chain', 'is_active' => true]);

        ApprovalChainStep::create([
            'approval_chain_id' => $chain->id,
            'user_id' => $user1->id,
            'order' => 1,
            'role' => 'approver',
            'timeout_hours' => 1,
            'timeout_action' => 'skip'
        ]);

        ApprovalChainStep::create([
            'approval_chain_id' => $chain->id,
            'user_id' => $user2->id,
            'order' => 2,
            'role' => 'approver'
        ]);

        $estimate = Estimate::factory()->create(['approval_chain_id' => $chain->id]);
        $estimate->submitForApproval();

        $this->travel(2)->hours();

        $this->artisan('approval:check-timeouts');

        // User 1 should be 'approved' (skipped).
        $user1Approval = $estimate->approvals()->where('user_id', $user1->id)->first();
        $this->assertEquals('approved', $user1Approval->status);
        $this->assertStringContainsString('skipped', strtolower($user1Approval->comments));

        // User 2 should be pending
        $this->assertEquals(1, $estimate->approvals()->where('user_id', $user2->id)->where('status', 'pending')->count());
    }

    public function test_fallback_user()
    {
        $user1 = User::factory()->create();
        $fallback = User::factory()->create();

        $chain = ApprovalChain::create([
            'name' => 'Fallback Chain',
            'is_active' => true,
            'fallback_user_id' => $fallback->id
        ]);

        ApprovalChainStep::create([
            'approval_chain_id' => $chain->id,
            'user_id' => $user1->id,
            'order' => 1,
            'role' => 'approver'
        ]);

        // Delete user 1
        $user1->delete();

        $estimate = Estimate::factory()->create(['approval_chain_id' => $chain->id]);
        $estimate->submitForApproval();

        // Approval should be assigned to fallback
        $approval = $estimate->approvals()->first();
        $this->assertEquals($fallback->id, $approval->user_id);
    }
}

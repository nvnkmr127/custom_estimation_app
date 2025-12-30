<?php

namespace Tests\Feature;

use App\Models\ApprovalChain;
use App\Models\ApprovalChainStep;
use App\Models\Estimate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ApprovalChainTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_approval_chain_flow()
    {
        // 1. Setup Users
        $creator = User::factory()->create(['role' => 'estimator']);
        $approver1 = User::factory()->create(['role' => 'estimator_manager']);
        $approver2 = User::factory()->create(['role' => 'super_admin']);

        // 2. Setup Approval Chain
        $chain = ApprovalChain::create([
            'name' => 'Test Chain',
            'is_active' => true,
        ]);

        ApprovalChainStep::create([
            'approval_chain_id' => $chain->id,
            'user_id' => $approver1->id,
            'role' => 'estimator_manager',
            'order' => 1,
        ]);

        ApprovalChainStep::create([
            'approval_chain_id' => $chain->id,
            'user_id' => $approver2->id,
            'role' => 'super_admin',
            'order' => 2,
        ]);

        // 3. Create Estimate
        $estimate = Estimate::create([
            'estimate_number' => 'EST-TEST-001',
            'title' => 'Test Estimate',
            'client_id' => 1, // Assumptions: Client factory or seeded client might be needed if foreign key constraints exist. Using 1 hoping standard setup exists or disabled FKs for simplicity if not. Better to creat a client.
            'estimate_date' => now(),
            'currency' => 'USD',
            'status' => 'draft',
            'discount_type' => 'fixed',
            'created_by' => $creator->id,
            'approval_chain_id' => $chain->id,
        ]);

        // Fix for Client ID constraint if it exists. 
        // We'll create a client if needed, but usually Tests use RefreshDatabase so ID 1 might not exist.
        // Let's create a client properly.
        $client = \App\Models\Client::factory()->create();
        $estimate->update(['client_id' => $client->id]);

        // --- Step 1: Submission ---
        $this->actingAs($creator);
        $response = $this->post(route('estimates.submit', $estimate));
        $response->assertRedirect();

        $estimate->refresh();
        $this->assertEquals('submitted', $estimate->approval_status);

        // Assert Approver 1 has pending approval
        $this->assertDatabaseHas('estimate_approvals', [
            'estimate_id' => $estimate->id,
            'user_id' => $approver1->id,
            'status' => 'pending',
        ]);

        // Assert Approver 2 does NOT have pending approval yet
        $this->assertDatabaseMissing('estimate_approvals', [
            'estimate_id' => $estimate->id,
            'user_id' => $approver2->id,
        ]);

        // --- Step 2: Approver 1 Approvals ---
        $this->actingAs($approver1);
        $response = $this->post(route('estimates.approve', $estimate), [
            'comments' => 'Looks good part 1',
        ]);
        $response->assertRedirect();

        $estimate->refresh();
        // Status should still be submitted (waiting for 2nd approver)
        $this->assertEquals('submitted', $estimate->approval_status);

        // Assert Approver 1 is approved
        $this->assertDatabaseHas('estimate_approvals', [
            'estimate_id' => $estimate->id,
            'user_id' => $approver1->id,
            'status' => 'approved',
        ]);

        // Assert Approver 2 NOW has pending approval
        $this->assertDatabaseHas('estimate_approvals', [
            'estimate_id' => $estimate->id,
            'user_id' => $approver2->id,
            'status' => 'pending',
        ]);

        // --- Step 3: Approver 2 Approvals ---
        $this->actingAs($approver2);
        $response = $this->post(route('estimates.approve', $estimate), [
            'comments' => 'Looks good part 2',
        ]);
        $response->assertRedirect();

        $estimate->refresh();
        // Fully approved now
        $this->assertEquals('approved', $estimate->approval_status);
        $this->assertEquals('approved', $estimate->status);
    }

    public function test_approval_chain_rejection()
    {
        // 1. Setup Users
        $creator = User::factory()->create(['role' => 'estimator']);
        $approver1 = User::factory()->create(['role' => 'estimator_manager']);
        $approver2 = User::factory()->create(['role' => 'super_admin']);

        // 2. Setup Approval Chain
        $chain = ApprovalChain::create([
            'name' => 'Test Chain',
            'is_active' => true,
        ]);

        ApprovalChainStep::create([
            'approval_chain_id' => $chain->id,
            'user_id' => $approver1->id,
            'role' => 'estimator_manager',
            'order' => 1,
        ]);

        ApprovalChainStep::create([
            'approval_chain_id' => $chain->id,
            'user_id' => $approver2->id,
            'role' => 'super_admin',
            'order' => 2,
        ]);

        // 3. Create Estimate
        $client = \App\Models\Client::factory()->create();
        $estimate = Estimate::create([
            'estimate_number' => 'EST-TEST-002',
            'title' => 'Test Estimate Reject',
            'client_id' => $client->id,
            'estimate_date' => now(),
            'currency' => 'USD',
            'status' => 'draft',
            'discount_type' => 'fixed',
            'created_by' => $creator->id,
            'approval_chain_id' => $chain->id,
        ]);

        // --- Step 1: Submission ---
        $this->actingAs($creator);
        $this->post(route('estimates.submit', $estimate));

        // --- Step 2: Approver 1 Rejects ---
        $this->actingAs($approver1);
        $response = $this->post(route('estimates.reject', $estimate), [
            'comments' => 'Bad estimate',
        ]);

        $estimate->refresh();
        $this->assertEquals('rejected', $estimate->approval_status);
        $this->assertNotEquals('approved', $estimate->status);

        // Assert Approver 1 is rejected
        $this->assertDatabaseHas('estimate_approvals', [
            'estimate_id' => $estimate->id,
            'user_id' => $approver1->id,
            'status' => 'rejected',
        ]);

        // Assert Approver 2 was NEVER notified
        $this->assertDatabaseMissing('estimate_approvals', [
            'estimate_id' => $estimate->id,
            'user_id' => $approver2->id,
        ]);
    }
}

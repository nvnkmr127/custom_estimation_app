<?php

namespace Tests\Feature;

use App\Models\ApprovalChain;
use App\Models\ApprovalChainStep;
use App\Models\ApprovalChecklist;
use App\Models\Client;
use App\Models\Estimate;
use App\Models\EstimateApproval;
use App\Models\EstimateItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalChecklistTest extends TestCase
{
    use RefreshDatabase;

    public function test_approver_cannot_approve_if_mandatory_checklist_incomplete()
    {
        $approver = User::factory()->create();
        $user = User::factory()->create();

        // Create Approval Chain
        $chain = ApprovalChain::create(['name' => 'Test Chain', 'is_active' => true]);
        ApprovalChainStep::create(['approval_chain_id' => $chain->id, 'user_id' => $approver->id, 'role' => 'manager', 'order' => 1]);

        // Create Mandatory Checklist
        $checklist = ApprovalChecklist::create(['task' => 'Verify Price', 'is_required' => true]);

        $client = Client::factory()->create();
        $estimate = Estimate::factory()->create([
            'estimate_number' => 'EST-001',
            'client_id' => $client->id,
            'approval_chain_id' => $chain->id,
            'created_by' => $user->id,
            'estimate_status' => Estimate::EST_STATUS_PENDING_APPROVAL,
            'approval_status' => Estimate::APP_STATUS_WAITING,
        ]);

        EstimateItem::create([
            'estimate_id' => $estimate->id,
            'name' => 'Line Item',
            'unit_price' => 100,
            'quantity' => 1,
            'unit_type' => 'nos',
            'total' => 100,
            'order_index' => 0,
        ]);

        EstimateApproval::create([
            'estimate_id' => $estimate->id,
            'user_id' => $approver->id,
            'status' => 'pending',
        ]);

        $this->actingAs($approver);

        // Attempt to approve without checklist
        $response = $this->post(route('estimates.approve', $estimate), [
            'comments' => 'Looks good',
        ]);

        $response->assertSessionHas('error', 'You must complete the mandatory checklist items before approving.');
        $this->assertEquals(Estimate::APP_STATUS_WAITING, $estimate->fresh()->approval_status);
    }

    public function test_approver_can_approve_if_mandatory_checklist_complete()
    {
        $approver = User::factory()->create();

        // Setup similar to above
        $chain = ApprovalChain::create(['name' => 'Test Chain', 'is_active' => true]);
        ApprovalChainStep::create(['approval_chain_id' => $chain->id, 'user_id' => $approver->id, 'role' => 'manager', 'order' => 1]);
        $checklist = ApprovalChecklist::create(['task' => 'Verify Price', 'is_required' => true]);
        $client = Client::factory()->create();
        $estimate = Estimate::factory()->create([
            'estimate_number' => 'EST-002',
            'client_id' => $client->id,
            'approval_chain_id' => $chain->id,
            'created_by' => User::factory()->create()->id,
            'estimate_status' => Estimate::EST_STATUS_PENDING_APPROVAL,
            'approval_status' => Estimate::APP_STATUS_WAITING,
        ]);

        EstimateItem::create([
            'estimate_id' => $estimate->id,
            'name' => 'Line Item',
            'unit_price' => 100,
            'quantity' => 1,
            'unit_type' => 'nos',
            'total' => 100,
            'order_index' => 0,
        ]);
        EstimateApproval::create([
            'estimate_id' => $estimate->id,
            'user_id' => $approver->id,
            'status' => 'pending',
        ]);

        $this->actingAs($approver);

        // Complete Checklist
        $this->postJson(route('estimates.toggle-checklist', $estimate), [
            'checklist_id' => $checklist->id,
            'completed' => true,
        ])->assertSuccessful();

        // Attempt to approve
        $response = $this->post(route('estimates.approve', $estimate), [
            'comments' => 'Approved now',
        ]);

        $response->assertSessionHas('success', 'Estimate approved successfully.');
        $this->assertEquals('approved', $estimate->fresh()->approval_status);
    }
}

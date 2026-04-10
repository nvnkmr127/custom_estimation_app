<?php

namespace Tests\Feature;

use App\Models\ApprovalChain;
use App\Models\ApprovalChainStep;
use App\Models\ApprovalChecklist;
use App\Models\Estimate;
use App\Models\EstimateItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_estimate_can_be_submitted_for_approval()
    {
        $user = User::factory()->create(['role' => 'estimator']);
        $approver = User::factory()->create(['role' => 'sales_manager']);

        $chain = ApprovalChain::create(['name' => 'Test Chain', 'currency' => 'USD', 'is_active' => true]);
        ApprovalChainStep::create([
            'approval_chain_id' => $chain->id,
            'user_id' => $approver->id,
            'role' => 'sales_manager',
            'order' => 1,
        ]);

        $estimate = Estimate::factory()->create([
            'created_by' => $user->id,
            'approval_chain_id' => $chain->id,
            'estimate_status' => Estimate::EST_STATUS_DRAFT,
            'approval_status' => Estimate::APP_STATUS_NOT_REQUIRED,
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

        $this->actingAs($user)
            ->post(route('estimates.submit', $estimate))
            ->assertRedirect();

        $this->assertDatabaseHas('estimates', [
            'id' => $estimate->id,
            'approval_status' => Estimate::APP_STATUS_WAITING,
        ]);

        $this->assertDatabaseHas('estimate_approvals', [
            'estimate_id' => $estimate->id,
            'user_id' => $approver->id,
            'status' => 'pending',
        ]);
    }

    public function test_approver_can_request_changes()
    {
        $approver = User::factory()->create(['role' => 'sales_manager']);
        $chain = ApprovalChain::create(['name' => 'Test Chain', 'currency' => 'USD', 'is_active' => true]);
        ApprovalChainStep::create([
            'approval_chain_id' => $chain->id,
            'user_id' => $approver->id,
            'role' => 'sales_manager',
            'order' => 1,
        ]);

        $estimate = Estimate::factory()->create([
            'approval_chain_id' => $chain->id,
            'estimate_status' => Estimate::EST_STATUS_DRAFT,
            'approval_status' => Estimate::APP_STATUS_NOT_REQUIRED,
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

        $estimate = app(\App\Services\Estimates\EstimateWorkflowService::class)->submitForApproval($estimate);

        $this->actingAs($approver)
            ->post(route('estimates.request-changes', $estimate), [
                'comments' => 'Please fix unit price',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('estimates', [
            'id' => $estimate->id,
            'estimate_status' => Estimate::EST_STATUS_DRAFT,
            'approval_status' => Estimate::APP_STATUS_CHANGES_REQUESTED,
        ]);
    }

    public function test_approver_cannot_approve_mandatory_checklist_incomplete()
    {
        $approver = User::factory()->create(['role' => 'sales_manager']);
        $chain = ApprovalChain::create(['name' => 'Test Chain', 'currency' => 'USD', 'is_active' => true]);
        ApprovalChainStep::create([
            'approval_chain_id' => $chain->id,
            'user_id' => $approver->id,
            'role' => 'sales_manager',
            'order' => 1,
        ]);

        // Create mandatory checklist
        ApprovalChecklist::create(['task' => 'Check Margin', 'is_required' => true]);

        $estimate = Estimate::factory()->create([
            'approval_chain_id' => $chain->id,
            'estimate_status' => Estimate::EST_STATUS_DRAFT,
            'approval_status' => Estimate::APP_STATUS_NOT_REQUIRED,
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

        $estimate = app(\App\Services\Estimates\EstimateWorkflowService::class)->submitForApproval($estimate);

        $this->actingAs($approver)
            ->post(route('estimates.approve', $estimate), ['comments' => 'LGTM'])
            ->assertSessionHas('error'); // Should fail

        $this->assertDatabaseHas('estimate_approvals', [
            'estimate_id' => $estimate->id,
            'status' => 'pending',
        ]);
    }
}

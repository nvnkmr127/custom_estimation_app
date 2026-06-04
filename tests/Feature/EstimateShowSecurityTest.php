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

class EstimateShowSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthorized_user_cannot_view_estimate_details()
    {
        $creator = User::factory()->create(['role' => 'estimator']);
        $unauthorizedUser = User::factory()->create(['role' => 'estimator']);

        $estimate = Estimate::factory()->create([
            'estimate_number' => 'EST-PRIVATE-1',
            'created_by' => $creator->id,
            'client_id' => 1
        ]);

        $response = $this->actingAs($unauthorizedUser)->get(route('estimates.show', $estimate));
        $response->assertStatus(403);
    }

    public function test_unauthorized_user_cannot_toggle_checklist_items()
    {
        $creator = User::factory()->create(['role' => 'estimator']);
        $unauthorizedUser = User::factory()->create(['role' => 'estimator']);

        $estimate = Estimate::factory()->create([
            'estimate_number' => 'EST-PRIVATE-2',
            'created_by' => $creator->id,
            'client_id' => 1,
            'estimate_status' => Estimate::EST_STATUS_PENDING_APPROVAL,
            'approval_status' => Estimate::APP_STATUS_WAITING,
        ]);

        $checklist = ApprovalChecklist::create(['task' => 'Review costings', 'is_required' => true]);

        // Attempting POST toggle route directly
        $response = $this->actingAs($unauthorizedUser)->post(route('estimates.toggle-checklist', $estimate), [
            'checklist_id' => $checklist->id,
            'completed' => true
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_override_and_see_approval_controls_in_livewire()
    {
        $creator = User::factory()->create(['role' => 'estimator']);
        $approver = User::factory()->create(['role' => 'estimator_manager']);
        $admin = User::factory()->create(['role' => 'super_admin']);

        $chain = ApprovalChain::create(['name' => 'Review Chain', 'currency' => 'USD', 'is_active' => true]);
        ApprovalChainStep::create([
            'approval_chain_id' => $chain->id,
            'user_id' => $approver->id,
            'role' => 'estimator_manager',
            'order' => 1,
        ]);

        $estimate = Estimate::factory()->create([
            'estimate_number' => 'EST-OVERRIDE',
            'created_by' => $creator->id,
            'client_id' => 1,
            'approval_chain_id' => $chain->id,
            'estimate_status' => Estimate::EST_STATUS_DRAFT,
            'approval_status' => Estimate::APP_STATUS_NOT_REQUIRED,
        ]);

        EstimateItem::create([
            'estimate_id' => $estimate->id,
            'name' => 'Item 1',
            'unit_price' => 100,
            'quantity' => 1,
            'unit_type' => 'nos',
            'total' => 100,
            'order_index' => 0,
        ]);

        // Submit for approval
        app(\App\Services\Estimates\EstimateWorkflowService::class)->submitForApproval($estimate);

        // 1. Approver sees controls because they have a pending approval record
        $this->actingAs($approver);
        $component = \Livewire\Livewire::test(\App\Livewire\Estimates\ShowEstimate::class, ['estimate' => $estimate]);
        $component->assertSet('adminApprovalOverride', false);

        // 2. Admin sees override controls even though they are not the assigned approver
        $this->actingAs($admin);
        $component2 = \Livewire\Livewire::test(\App\Livewire\Estimates\ShowEstimate::class, ['estimate' => $estimate]);
        $component2->assertSet('adminApprovalOverride', true);
    }

    public function test_admin_override_can_force_approve_workflow()
    {
        $creator = User::factory()->create(['role' => 'estimator']);
        $approver = User::factory()->create(['role' => 'estimator_manager']);
        $admin = User::factory()->create(['role' => 'super_admin']);

        $chain = ApprovalChain::create(['name' => 'Review Chain', 'currency' => 'USD', 'is_active' => true]);
        ApprovalChainStep::create([
            'approval_chain_id' => $chain->id,
            'user_id' => $approver->id,
            'role' => 'estimator_manager',
            'order' => 1,
        ]);

        $estimate = Estimate::factory()->create([
            'estimate_number' => 'EST-FORCE-APPROVE',
            'created_by' => $creator->id,
            'client_id' => 1,
            'approval_chain_id' => $chain->id,
            'estimate_status' => Estimate::EST_STATUS_DRAFT,
            'approval_status' => Estimate::APP_STATUS_NOT_REQUIRED,
        ]);

        EstimateItem::create([
            'estimate_id' => $estimate->id,
            'name' => 'Item 1',
            'unit_price' => 100,
            'quantity' => 1,
            'unit_type' => 'nos',
            'total' => 100,
            'order_index' => 0,
        ]);

        // Submit for approval
        app(\App\Services\Estimates\EstimateWorkflowService::class)->submitForApproval($estimate);

        // Force approve as Admin via Livewire action
        $this->actingAs($admin);
        \Livewire\Livewire::test(\App\Livewire\Estimates\ShowEstimate::class, ['estimate' => $estimate])
            ->call('approve', 'Override by admin');

        $this->assertDatabaseHas('estimates', [
            'id' => $estimate->id,
            'estimate_status' => Estimate::EST_STATUS_APPROVED,
            'approval_status' => Estimate::APP_STATUS_APPROVED,
        ]);

        $this->assertDatabaseHas('estimate_approvals', [
            'estimate_id' => $estimate->id,
            'user_id' => $admin->id,
            'status' => 'approved',
            'comments' => 'Override by admin (Force Approved by Admin)',
        ]);
    }
}

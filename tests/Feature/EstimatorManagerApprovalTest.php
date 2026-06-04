<?php

namespace Tests\Feature;

use App\Models\ApprovalChain;
use App\Models\ApprovalChainStep;
use App\Models\Client;
use App\Models\Estimate;
use App\Models\EstimateItem;
use App\Models\User;
use App\Models\RolePermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstimatorManagerApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_estimator_manager_can_approve_via_override()
    {
        // 1. Create users
        $creator = User::factory()->create(['role' => 'estimator']);
        $assignedApprover = User::factory()->create(['role' => 'estimator_manager']);
        $managerOverride = User::factory()->create(['role' => 'estimator_manager']);

        // 2. Set up approve_estimates permission for estimator_manager role
        RolePermission::firstOrCreate([
            'role' => 'estimator_manager',
            'permission' => 'approve_estimates'
        ]);
        \App\Services\PermissionService::clearCache('estimator_manager');

        // 3. Create chain & step assigned to $assignedApprover
        $chain = ApprovalChain::create(['name' => 'Manager Chain', 'is_active' => true]);
        ApprovalChainStep::create([
            'approval_chain_id' => $chain->id,
            'user_id' => $assignedApprover->id,
            'role' => 'estimator_manager',
            'order' => 1,
        ]);

        $client = Client::factory()->create();
        $estimate = Estimate::factory()->create([
            'estimate_number' => 'EST-MGR-OVERRIDE',
            'created_by' => $creator->id,
            'client_id' => $client->id,
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

        // Get fresh copy
        $freshEstimate = $estimate->fresh();

        // 4. Test state policy for the override manager
        $policy = app(\App\Services\Estimates\EstimateStateService::class)->getPolicy($freshEstimate, $managerOverride);
        $this->assertTrue($policy['can_approve']);

        // 5. Test Livewire component visibility of override controls
        $this->actingAs($managerOverride);
        $component = \Livewire\Livewire::test(\App\Livewire\Estimates\ShowEstimate::class, ['estimate' => $freshEstimate]);
        $component->assertSet('adminApprovalOverride', true);

        // 6. Execute the override approval via Livewire
        $component->call('approve', 'Manager Override');

        // 7. Verify database matches
        $this->assertEquals(Estimate::EST_STATUS_APPROVED, $freshEstimate->fresh()->estimate_status);
        $this->assertEquals(Estimate::APP_STATUS_APPROVED, $freshEstimate->fresh()->approval_status);
    }

    public function test_estimator_without_permission_cannot_approve_via_override()
    {
        $creator = User::factory()->create(['role' => 'estimator']);
        $assignedApprover = User::factory()->create(['role' => 'estimator_manager']);
        $otherEstimator = User::factory()->create(['role' => 'estimator']);

        // Ensure estimator_manager has approve_estimates, but estimator does not
        RolePermission::firstOrCreate([
            'role' => 'estimator_manager',
            'permission' => 'approve_estimates'
        ]);
        \App\Services\PermissionService::clearCache('estimator_manager');
        \App\Services\PermissionService::clearCache('estimator');

        $chain = ApprovalChain::create(['name' => 'Manager Chain', 'is_active' => true]);
        ApprovalChainStep::create([
            'approval_chain_id' => $chain->id,
            'user_id' => $assignedApprover->id,
            'role' => 'estimator_manager',
            'order' => 1,
        ]);

        $client = Client::factory()->create();
        // Set created_by to $otherEstimator so they can view the estimate
        $estimate = Estimate::factory()->create([
            'estimate_number' => 'EST-MGR-NO-OVERRIDE',
            'created_by' => $otherEstimator->id,
            'client_id' => $client->id,
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

        app(\App\Services\Estimates\EstimateWorkflowService::class)->submitForApproval($estimate);

        // Get fresh copy
        $freshEstimate = $estimate->fresh();

        // State policy check
        $policy = app(\App\Services\Estimates\EstimateStateService::class)->getPolicy($freshEstimate, $otherEstimator);
        $this->assertFalse($policy['can_approve']);

        // Livewire component visibility of override controls
        $this->actingAs($otherEstimator);
        $component = \Livewire\Livewire::test(\App\Livewire\Estimates\ShowEstimate::class, ['estimate' => $freshEstimate]);
        $component->assertSet('adminApprovalOverride', false);

        // Attempting to approve should fail
        $component->call('approve', 'Unauthorized');
        $this->assertNotEquals(Estimate::EST_STATUS_APPROVED, $freshEstimate->fresh()->estimate_status);
        $this->assertNotEquals(Estimate::APP_STATUS_APPROVED, $freshEstimate->fresh()->approval_status);
    }
}

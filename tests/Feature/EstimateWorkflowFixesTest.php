<?php

namespace Tests\Feature;

use App\Models\ApprovalChain;
use App\Models\ApprovalChainStep;
use App\Models\Client;
use App\Models\Estimate;
use App\Models\EstimateApproval;
use App\Models\User;
use App\Livewire\Estimates\ShowEstimate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EstimateWorkflowFixesTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function snapshot_version_is_fillable_and_saved()
    {
        $owner = User::factory()->create();
        $client = Client::factory()->create();
        
        $estimate = Estimate::factory()->create([
            'created_by' => $owner->id,
            'client_id' => $client->id,
            'lock_version' => 3,
        ]);

        $approval = EstimateApproval::create([
            'estimate_id' => $estimate->id,
            'user_id' => $owner->id,
            'order' => 1,
            'status' => 'pending',
            'snapshot_version' => 2,
        ]);

        $this->assertEquals(2, $approval->fresh()->snapshot_version);
    }

    /** @test */
    public function admin_override_sees_version_mismatch_warning_if_any_pending_approval_has_mismatch()
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create(['role' => 'super_admin']);
        $client = Client::factory()->create();
        
        // Lock version is 5
        $estimate = Estimate::factory()->create([
            'created_by' => $owner->id,
            'client_id' => $client->id,
            'lock_version' => 5,
            'estimate_status' => Estimate::EST_STATUS_PENDING_APPROVAL,
            'approval_status' => Estimate::APP_STATUS_WAITING,
        ]);

        // Creating a pending approval with snapshot version 4 (mismatched)
        EstimateApproval::create([
            'estimate_id' => $estimate->id,
            'user_id' => $owner->id,
            'order' => 1,
            'status' => 'pending',
            'snapshot_version' => 4,
        ]);

        // Admin override can approve (role is super_admin), so they see approval controls.
        // We assert versionMismatch is set to true.
        Livewire::actingAs($admin)
            ->test(ShowEstimate::class, ['estimate' => $estimate])
            ->assertSet('versionMismatch', true);
    }

    /** @test */
    public function wrong_exception_caught_fixed_in_add_comment()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $client = Client::factory()->create();
        
        $estimate = Estimate::factory()->create([
            'created_by' => $owner->id,
            'client_id' => $client->id,
        ]);

        // When unauthorized user tries to add a comment, the exception is caught,
        // session has 'error' flash message, and database does not get the comment.
        Livewire::actingAs($otherUser)
            ->test(ShowEstimate::class, ['estimate' => $estimate])
            ->call('addComment', 'Some comment');

        $this->assertEquals(0, $estimate->comments()->count());
    }

    /** @test */
    public function approve_version_re_locks_and_saves_correctly()
    {
        $owner = User::factory()->create();
        $client = Client::factory()->create();
        
        $estimate = Estimate::factory()->create([
            'created_by' => $owner->id,
            'client_id' => $client->id,
            'version' => 2,
            'is_current_version' => false,
        ]);

        $this->actingAs($owner)
            ->post(route('estimates.approve-version', $estimate))
            ->assertRedirect();

        $this->assertEquals(1, $estimate->fresh()->is_current_version);
    }

    /** @test */
    public function admin_cannot_approve_mismatched_estimate()
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create(['role' => 'super_admin']);
        $client = Client::factory()->create();

        $estimate = Estimate::factory()->create([
            'created_by' => $owner->id,
            'client_id' => $client->id,
            'lock_version' => 5,
            'estimate_status' => Estimate::EST_STATUS_PENDING_APPROVAL,
            'approval_status' => Estimate::APP_STATUS_WAITING,
        ]);

        // Create a pending approval with snapshot version 4 (mismatched)
        EstimateApproval::create([
            'estimate_id' => $estimate->id,
            'user_id' => $owner->id,
            'order' => 1,
            'status' => 'pending',
            'snapshot_version' => 4,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("The estimate content has changed since this approval was requested. Please refresh and review the latest version.");

        app(\App\Services\Estimates\EstimateWorkflowService::class)->approve($estimate, $admin->id, 'Force approve');
    }

    /** @test */
    public function admin_cannot_reject_mismatched_estimate()
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create(['role' => 'super_admin']);
        $client = Client::factory()->create();

        $estimate = Estimate::factory()->create([
            'created_by' => $owner->id,
            'client_id' => $client->id,
            'lock_version' => 5,
            'estimate_status' => Estimate::EST_STATUS_PENDING_APPROVAL,
            'approval_status' => Estimate::APP_STATUS_WAITING,
        ]);

        // Create a pending approval with snapshot version 4 (mismatched)
        EstimateApproval::create([
            'estimate_id' => $estimate->id,
            'user_id' => $owner->id,
            'order' => 1,
            'status' => 'pending',
            'snapshot_version' => 4,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("The estimate content has changed. Review the latest version before rejecting.");

        app(\App\Services\Estimates\EstimateWorkflowService::class)->reject($estimate, $admin->id, 'Force reject');
    }

    /** @test */
    public function admin_cannot_request_changes_on_mismatched_estimate()
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create(['role' => 'super_admin']);
        $client = Client::factory()->create();

        $estimate = Estimate::factory()->create([
            'created_by' => $owner->id,
            'client_id' => $client->id,
            'lock_version' => 5,
            'estimate_status' => Estimate::EST_STATUS_PENDING_APPROVAL,
            'approval_status' => Estimate::APP_STATUS_WAITING,
        ]);

        // Create a pending approval with snapshot version 4 (mismatched)
        EstimateApproval::create([
            'estimate_id' => $estimate->id,
            'user_id' => $owner->id,
            'order' => 1,
            'status' => 'pending',
            'snapshot_version' => 4,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("The estimate content has changed. Review the latest version before requesting changes.");

        app(\App\Services\Estimates\EstimateWorkflowService::class)->requestChanges($estimate, $admin->id, 'Request changes');
    }
}

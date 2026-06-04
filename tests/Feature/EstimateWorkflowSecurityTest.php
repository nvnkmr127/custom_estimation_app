<?php

namespace Tests\Feature;

use App\Models\ApprovalChain;
use App\Models\ApprovalChainStep;
use App\Models\Estimate;
use App\Models\EstimateItem;
use App\Models\User;
use App\Models\EstimateComment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstimateWorkflowSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $creator;
    private User $unauthorizedUser;
    private Estimate $estimate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->creator = User::factory()->create(['role' => 'estimator']);
        $this->unauthorizedUser = User::factory()->create(['role' => 'estimator']);

        $this->estimate = Estimate::factory()->create([
            'estimate_number' => 'EST-TEST-100',
            'created_by' => $this->creator->id,
            'client_id' => 1,
            'estimate_status' => Estimate::EST_STATUS_DRAFT,
            'approval_status' => Estimate::APP_STATUS_NOT_REQUIRED,
        ]);

        EstimateItem::create([
            'estimate_id' => $this->estimate->id,
            'name' => 'Test Item',
            'unit_price' => 200,
            'quantity' => 1,
            'unit_type' => 'nos',
            'total' => 200,
            'order_index' => 0,
        ]);
    }

    public function test_unauthorized_user_cannot_submit_estimate_for_approval()
    {
        $response = $this->actingAs($this->unauthorizedUser)
            ->post(route('estimates.submit', $this->estimate));

        $response->assertStatus(403);
    }

    public function test_unauthorized_user_cannot_approve_reject_or_request_changes()
    {
        // 1. Submit for approval so it is waiting
        $this->actingAs($this->creator)
            ->post(route('estimates.submit', $this->estimate));

        $this->estimate->refresh();

        // 2. Try to approve as unauthorized user
        $responseApprove = $this->actingAs($this->unauthorizedUser)
            ->post(route('estimates.approve', $this->estimate), ['comments' => 'Steal approval']);
        $responseApprove->assertStatus(403);

        // 3. Try to reject
        $responseReject = $this->actingAs($this->unauthorizedUser)
            ->post(route('estimates.reject', $this->estimate), ['comments' => 'Fake reject']);
        $responseReject->assertStatus(403);

        // 4. Try to request changes
        $responseChanges = $this->actingAs($this->unauthorizedUser)
            ->post(route('estimates.request-changes', $this->estimate), ['comments' => 'Fake request changes']);
        $responseChanges->assertStatus(403);
    }

    public function test_unauthorized_user_cannot_retrieve_or_restore_deleted_history()
    {
        // 1. Try to get restore history
        $responseHistory = $this->actingAs($this->unauthorizedUser)
            ->get(route('estimates.restore-history', $this->estimate));
        $responseHistory->assertStatus(403);

        // 2. Try to restore session
        $responseSession = $this->actingAs($this->unauthorizedUser)
            ->post(route('estimates.restore-session', $this->estimate), ['timestamp' => now()->toDateTimeString()]);
        $responseSession->assertStatus(403);
    }

    public function test_unauthorized_user_cannot_create_version()
    {
        $response = $this->actingAs($this->unauthorizedUser)
            ->post(route('estimates.version', $this->estimate));

        $response->assertStatus(403);
    }

    public function test_view_only_follower_cannot_post_internal_comments()
    {
        $viewOnlyFollower = User::factory()->create(['role' => 'estimator']);
        $this->estimate->manualFollowers()->create([
            'user_id' => $viewOnlyFollower->id,
            'permissions' => ['view'], // View only, no edit/update
        ]);

        $response = $this->actingAs($viewOnlyFollower)
            ->post(route('comments.store', $this->estimate), [
                'commentable_type' => Estimate::class,
                'commentable_id' => $this->estimate->id,
                'comment' => 'Sneaking a comment in.',
            ]);

        $response->assertStatus(403);

        // Creator (who has update access) should be able to post
        $responseSuccess = $this->actingAs($this->creator)
            ->post(route('comments.store', $this->estimate), [
                'commentable_type' => Estimate::class,
                'commentable_id' => $this->estimate->id,
                'comment' => 'Valid comment.',
            ]);

        $responseSuccess->assertStatus(200);
    }
}

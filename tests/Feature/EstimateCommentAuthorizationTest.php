<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Estimate;
use App\Models\EstimateComment;
use App\Models\User;
use App\Livewire\Estimates\ShowEstimate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EstimateCommentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function authorized_user_can_toggle_comment_status_on_their_estimate()
    {
        $owner = User::factory()->create();
        $client = Client::factory()->create();
        
        $estimate = Estimate::factory()->create([
            'created_by' => $owner->id,
            'client_id' => $client->id,
        ]);

        $comment = EstimateComment::create([
            'estimate_id' => $estimate->id,
            'commentable_type' => Estimate::class,
            'commentable_id' => $estimate->id,
            'user_id' => $owner->id,
            'comment' => 'Test comment',
            'type' => 'internal',
            'status' => 'pending',
        ]);

        Livewire::actingAs($owner)
            ->test(ShowEstimate::class, ['estimate' => $estimate])
            ->call('toggleCommentStatus', $comment->id, 'pending');

        $this->assertEquals('clarified', $comment->fresh()->status);
    }

    /** @test */
    public function unauthorized_user_cannot_toggle_comment_status()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $client = Client::factory()->create();
        
        $estimate = Estimate::factory()->create([
            'created_by' => $owner->id,
            'client_id' => $client->id,
        ]);

        $comment = EstimateComment::create([
            'estimate_id' => $estimate->id,
            'commentable_type' => Estimate::class,
            'commentable_id' => $estimate->id,
            'user_id' => $owner->id,
            'comment' => 'Test comment',
            'type' => 'internal',
            'status' => 'pending',
        ]);

        $this->withoutExceptionHandling();
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        Livewire::actingAs($otherUser)
            ->test(ShowEstimate::class, ['estimate' => $estimate]);
    }

    /** @test */
    public function view_only_follower_cannot_toggle_comment_status()
    {
        $owner = User::factory()->create();
        $viewOnlyUser = User::factory()->create(['role' => 'estimator']);
        $client = Client::factory()->create();
        
        $estimate = Estimate::factory()->create([
            'created_by' => $owner->id,
            'client_id' => $client->id,
        ]);

        $estimate->manualFollowers()->create([
            'user_id' => $viewOnlyUser->id,
            'permissions' => ['view'],
        ]);

        $comment = EstimateComment::create([
            'estimate_id' => $estimate->id,
            'commentable_type' => Estimate::class,
            'commentable_id' => $estimate->id,
            'user_id' => $owner->id,
            'comment' => 'Test comment',
            'type' => 'internal',
            'status' => 'pending',
        ]);

        Livewire::actingAs($viewOnlyUser)
            ->test(ShowEstimate::class, ['estimate' => $estimate])
            ->call('toggleCommentStatus', $comment->id, 'pending');

        $this->assertEquals('pending', $comment->fresh()->status);
    }

    /** @test */
    public function user_cannot_toggle_comment_status_of_a_comment_belonging_to_another_estimate()
    {
        $owner = User::factory()->create();
        $client = Client::factory()->create();
        
        $estimate1 = Estimate::factory()->create([
            'created_by' => $owner->id,
            'client_id' => $client->id,
        ]);

        $estimate2 = Estimate::factory()->create([
            'created_by' => User::factory()->create()->id,
            'client_id' => $client->id,
        ]);

        // Comment belongs to estimate 2
        $comment = EstimateComment::create([
            'estimate_id' => $estimate2->id,
            'commentable_type' => Estimate::class,
            'commentable_id' => $estimate2->id,
            'user_id' => $owner->id,
            'comment' => 'Test comment',
            'type' => 'internal',
            'status' => 'pending',
        ]);

        // Attempting to toggle comment status of $comment (belongs to estimate2) 
        // through $estimate1's Livewire component should fail
        Livewire::actingAs($owner)
            ->test(ShowEstimate::class, ['estimate' => $estimate1])
            ->call('toggleCommentStatus', $comment->id, 'pending');

        // Status should still be pending
        $this->assertEquals('pending', $comment->fresh()->status);
    }
}

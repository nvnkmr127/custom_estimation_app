<?php

namespace Tests\Feature;

use App\Models\Estimate;
use App\Models\User;
use App\Models\EstimateComment;
use App\Notifications\EstimateCommentNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationEnhancementTest extends TestCase
{
    use RefreshDatabase;

    public function test_estimate_comment_notification_is_stored_in_database()
    {
        // Create user and estimate
        $user = User::factory()->create();
        $estimate = Estimate::factory()->create();

        // Create a comment
        $comment = new EstimateComment();
        $comment->comment = "This is a test comment";
        $comment->user_id = $user->id;
        $comment->estimate_id = $estimate->id;
        $comment->commentable_type = Estimate::class;
        $comment->commentable_id = $estimate->id;
        $comment->type = 'internal';
        $comment->save();

        // Send notification
        $notification = new EstimateCommentNotification($comment, $estimate);
        $user->notify($notification);

        // Assert database has notification
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'type' => EstimateCommentNotification::class,
        ]);

        // Fetch notification and check data
        $dbNotification = $user->notifications()->latest()->first();
        $this->assertEquals($estimate->id, $dbNotification->data['estimate_id']);
        $this->assertEquals('comment', $dbNotification->data['type']);

        return $user;
    }

    public function test_notification_index_view_renders_correctly()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'super_admin',
        ]);
        $estimate = Estimate::factory()->create();

        $comment = new EstimateComment();
        $comment->comment = "View Test Comment";
        $comment->user_id = $user->id;
        $comment->estimate_id = $estimate->id;
        $comment->commentable_type = Estimate::class;
        $comment->commentable_id = $estimate->id;
        $comment->type = 'internal';
        $comment->save();

        $user->notify(new EstimateCommentNotification($comment, $estimate));

        // Check dashboard access
        $this->actingAs($user)->get(route('dashboard'))->assertStatus(200);

        // Check notifications access
        $response = $this->actingAs($user)->get(route('notifications.index'));

        $response->assertStatus(200);
        $response->assertSee('commented on Estimate');
        $response->assertSee('Notifications');
    }
}

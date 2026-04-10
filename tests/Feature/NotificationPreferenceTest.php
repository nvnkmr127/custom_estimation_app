<?php

namespace Tests\Feature;

use App\Core\Events\Estimates\EstimateViewed;
use App\Jobs\SendEmailJob;
use App\Models\NotificationPreference;
use App\Models\PendingNotification;
use App\Models\User;
use App\Models\Estimate;
use App\Models\Client;
use App\Listeners\MailListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_muted_notifications_are_not_sent_or_queued()
    {
        Queue::fake([SendEmailJob::class]);

        $user = User::factory()->create(['email' => 'user@example.com']);
        $estimate = Estimate::factory()->create(['created_by' => $user->id]);

        // Mute estimate.viewed
        NotificationPreference::create([
            'user_id' => $user->id,
            'event_type' => 'estimate.viewed',
            'channel' => 'email',
            'frequency' => NotificationPreference::FREQUENCY_MUTED,
        ]);

        // Fire event
        $event = new EstimateViewed($estimate, null, '1.1.1.1');
        $listener = app(MailListener::class);
        $listener->handle($event);

        // Assert no job pushed
        Queue::assertNotPushed(SendEmailJob::class);

        // Assert no pending notification
        $this->assertEquals(0, PendingNotification::count());
    }

    public function test_digest_notifications_are_queued_but_not_sent_immediately()
    {
        Queue::fake([SendEmailJob::class]);

        $user = User::factory()->create(['email' => 'user@example.com']);
        $estimate = Estimate::factory()->create(['created_by' => $user->id]);

        // Set to daily_digest
        NotificationPreference::create([
            'user_id' => $user->id,
            'event_type' => 'estimate.viewed',
            'channel' => 'email',
            'frequency' => NotificationPreference::FREQUENCY_DAILY,
        ]);

        // Fire event
        $event = new EstimateViewed($estimate, null, '1.1.1.1');
        $listener = app(MailListener::class);
        $listener->handle($event);

        // Assert no job pushed immediately
        Queue::assertNotPushed(SendEmailJob::class);

        // Assert pending notification created
        $this->assertEquals(1, PendingNotification::count());
        $pending = PendingNotification::first();
        $this->assertEquals('estimate.viewed', $pending->event_type);
        $this->assertEquals($user->id, $pending->user_id);
    }

    public function test_digest_command_sends_aggregated_email()
    {
        Queue::fake([SendEmailJob::class]);

        $user = User::factory()->create(['email' => 'user@example.com']);

        // Create pending notifications
        PendingNotification::create([
            'user_id' => $user->id,
            'event_type' => 'estimate.viewed',
            'template' => 'emails.estimates.viewed_notification',
            'payload' => ['subject' => 'Estimate Viewed #1', 'view_url' => 'http://test.com/1'],
            'frequency' => NotificationPreference::FREQUENCY_DAILY,
        ]);

        PendingNotification::create([
            'user_id' => $user->id,
            'event_type' => 'comment.added',
            'template' => 'emails.comments.notification',
            'payload' => ['subject' => 'New Comment #A', 'view_url' => 'http://test.com/A'],
            'frequency' => NotificationPreference::FREQUENCY_DAILY,
        ]);

        // Run command
        $this->artisan('notifications:send-digests daily_digest')
            ->assertExitCode(0);

        // Assert ONE email job pushed for the summary
        Queue::assertPushed(SendEmailJob::class, function ($job) use ($user) {
            return $job->to === $user->email &&
                str_contains($job->subject, 'Daily Digest') &&
                str_contains($job->body, 'Estimate Viewed #1') &&
                str_contains($job->body, 'New Comment #A');
        });

        // Assert they are marked as sent
        $this->assertEquals(0, PendingNotification::unsent()->count());
    }
}

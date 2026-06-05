<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemindersPageTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function reminders_index_shows_create_form()
    {
        $user = User::factory()->create([
            'role' => 'estimator',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('reminders.index'));

        $response->assertOk();
        $response->assertSee('Add Reminder');
        $response->assertSee('name="remindable_type"', false);
        $response->assertSee('name="remindable_id"', false);
    }

    /** @test */
    public function can_create_reminder_linked_to_estimate()
    {
        $user = User::factory()->create([
            'role' => 'estimator',
            'email_verified_at' => now(),
        ]);

        $client = \App\Models\Client::factory()->create();
        $estimate = \App\Models\Estimate::factory()->create([
            'client_id' => $client->id,
        ]);

        $reminderData = [
            'title' => 'Follow up on Estimate',
            'description' => 'Send approval reminder email',
            'remindable_type' => \App\Models\Estimate::class,
            'remindable_id' => $estimate->id,
            'remind_at' => now()->addDays(2)->toDateTimeString(),
            'type' => 'in_app',
        ];

        $response = $this->actingAs($user)->post(route('reminders.store'), $reminderData);

        $response->assertRedirect();
        $this->assertDatabaseHas('reminders', [
            'user_id' => $user->id,
            'title' => 'Follow up on Estimate',
            'remindable_type' => \App\Models\Estimate::class,
            'remindable_id' => $estimate->id,
            'type' => 'in_app',
        ]);
    }

    /** @test */
    public function send_reminders_command_delivers_correct_channels()
    {
        \Illuminate\Support\Facades\Notification::fake();

        $user = User::factory()->create([
            'role' => 'estimator',
            'email_verified_at' => now(),
        ]);

        // Create 1 due email reminder, 1 due in_app reminder, 1 upcoming reminder
        $emailReminder = \App\Models\Reminder::create([
            'user_id' => $user->id,
            'title' => 'Email Reminder',
            'remindable_type' => \App\Models\User::class,
            'remindable_id' => $user->id,
            'remind_at' => now()->subMinute(),
            'type' => 'email',
            'is_sent' => false,
        ]);

        $inAppReminder = \App\Models\Reminder::create([
            'user_id' => $user->id,
            'title' => 'In-App Reminder',
            'remindable_type' => \App\Models\User::class,
            'remindable_id' => $user->id,
            'remind_at' => now()->subMinute(),
            'type' => 'in_app',
            'is_sent' => false,
        ]);

        $upcomingReminder = \App\Models\Reminder::create([
            'user_id' => $user->id,
            'title' => 'Future Reminder',
            'remindable_type' => \App\Models\User::class,
            'remindable_id' => $user->id,
            'remind_at' => now()->addHour(),
            'type' => 'both',
            'is_sent' => false,
        ]);

        // Run SendReminders console command
        \Illuminate\Support\Facades\Artisan::call('reminders:send');

        // Assert notification sent with appropriate channels
        \Illuminate\Support\Facades\Notification::assertSentTo(
            $user,
            \App\Notifications\ReminderNotification::class,
            function ($notification, $channels) use ($user, $emailReminder, $inAppReminder) {
                // Determine if it matches one of the due reminders
                if ($notification->toArray($user)['title'] === 'Email Reminder') {
                    return in_array('mail', $channels) && !in_array('database', $channels);
                }
                if ($notification->toArray($user)['title'] === 'In-App Reminder') {
                    return in_array('database', $channels) && !in_array('mail', $channels);
                }
                return false;
            }
        );

        // Verify sent flags
        $this->assertTrue($emailReminder->fresh()->is_sent);
        $this->assertTrue($inAppReminder->fresh()->is_sent);
        $this->assertFalse($upcomingReminder->fresh()->is_sent);
    }
}

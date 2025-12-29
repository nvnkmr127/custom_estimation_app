<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $reminders = \App\Models\Reminder::due()->get();

        foreach ($reminders as $reminder) {
            $user = $reminder->user;

            // Send notification based on type
            if ($reminder->type === 'email' || $reminder->type === 'both') {
                $user->notify(new \App\Notifications\ReminderNotification($reminder));
            }

            // Mark as sent
            $reminder->markAsSent();

            $this->info("Reminder sent to {$user->name}: {$reminder->title}");
        }

        $this->info("Completed sending " . $reminders->count() . " reminders.");
    }
}

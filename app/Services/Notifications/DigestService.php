<?php

namespace App\Services\Notifications;

use App\Models\User;
use App\Models\PendingNotification;
use App\Services\Mail\EmailDispatcher;
use Illuminate\Support\Facades\Log;

class DigestService
{
    protected $emailDispatcher;

    public function __construct(EmailDispatcher $emailDispatcher)
    {
        $this->emailDispatcher = $emailDispatcher;
    }

    /**
     * Send digests to all users who have pending notifications for a given frequency.
     */
    public function sendDigests(string $frequency): void
    {
        $usersWithPending = User::whereHas('pendingNotifications', function ($query) use ($frequency) {
            $query->unsent()->where('frequency', $frequency);
        })->get();

        foreach ($usersWithPending as $user) {
            $this->sendUserDigest($user, $frequency);
        }
    }

    /**
     * Generate and send a digest for a specific user.
     */
    public function sendUserDigest(User $user, string $frequency): void
    {
        $notifications = PendingNotification::where('user_id', $user->id)
            ->unsent()
            ->where('frequency', $frequency)
            ->get();

        if ($notifications->isEmpty()) {
            return;
        }

        $title = ucwords(str_replace('_', ' ', $frequency));
        $subject = "Your $title - " . now()->format('M d, Y');

        try {
            $this->emailDispatcher->dispatch(
                $user->email,
                $subject,
                'emails.digests.summary',
                [
                    'recipient_name' => $user->name,
                    'notifications' => $notifications,
                    'frequency' => $frequency,
                    'date' => now()->format('M d, Y'),
                ]
            );

            // Mark as sent
            PendingNotification::whereIn('id', $notifications->pluck('id'))
                ->update(['sent_at' => now()]);

            Log::info("Sent $frequency to user {$user->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send $frequency to user {$user->id}: " . $e->getMessage());
        }
    }
}

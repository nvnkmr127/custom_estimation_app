<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BatchExportReady extends Notification implements ShouldQueue
{
    use Queueable;

    protected $downloadUrl;

    /**
     * Create a new notification instance.
     */
    public function __construct($downloadUrl)
    {
        $this->downloadUrl = $downloadUrl;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database']; // Also log to database if desired
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Estimate Export is Ready')
            ->line('The batch of estimates you requested has been generated.')
            ->action('Download ZIP', $this->downloadUrl)
            ->line('This link will remain valid for 24 hours.')
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Your batch export is ready.',
            'action_url' => $this->downloadUrl,
        ];
    }
}

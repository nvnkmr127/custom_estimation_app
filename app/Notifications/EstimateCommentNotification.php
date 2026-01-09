<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EstimateCommentNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public $comment;
    public $estimate;

    /**
     * Create a new notification instance.
     */
    public function __construct($comment, $estimate)
    {
        $this->comment = $comment;
        $this->estimate = $estimate;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $author = $this->comment->isClientComment()
            ? ($this->comment->client_name ?: 'Client')
            : ($this->comment->user->name ?? 'Staff');

        return (new MailMessage)
            ->subject("New Comment on Estimate #{$this->estimate->estimate_number}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$author} has added a new comment to Estimate #{$this->estimate->estimate_number}:")
            ->line("\"{$this->comment->comment}\"")
            ->action('View Estimate', route('estimates.show', $this->estimate));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}

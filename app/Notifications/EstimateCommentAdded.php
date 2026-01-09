<?php

namespace App\Notifications;

use App\Models\EstimateComment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EstimateCommentAdded extends Notification
{
    use Queueable;

    public $comment;

    /**
     * Create a new notification instance.
     */
    public function __construct(EstimateComment $comment)
    {
        $this->comment = $comment;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $estimate = $this->comment->estimate;
        $commenter = $this->comment->client_name ?: 'Client';

        return (new MailMessage)
            ->subject('New Comment on Estimate #' . $estimate->estimate_number)
            ->line($commenter . ' added a new comment on Estimate #' . $estimate->estimate_number)
            ->line('"' . $this->comment->comment . '"')
            ->action('View Estimate', route('estimates.show', $estimate))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $estimate = $this->comment->estimate;
        $commenter = $this->comment->client_name ?: 'Client';

        return [
            'estimate_id' => $estimate->id,
            'estimate_number' => $estimate->estimate_number,
            'comment_id' => $this->comment->id,
            'message' => $commenter . ' commented on Estimate #' . $estimate->estimate_number,
            'link' => route('estimates.show', $estimate) . '#comments', // Assuming there's a comments section/anchor
        ];
    }
}

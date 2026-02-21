<?php

namespace App\Notifications;

use App\Models\Estimate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EstimateStatusUpdated extends Notification
{
    use Queueable;

    public $estimate;
    public $status;
    public $comments;

    /**
     * Create a new notification instance.
     */
    public function __construct(Estimate $estimate, $status, $comments = null)
    {
        $this->estimate = $estimate;
        $this->status = $status;
        $this->comments = $comments;
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
        return (new MailMessage)
            ->subject('Estimate ' . ucfirst($this->status))
            ->line('Estimate #' . $this->estimate->estimate_number . ' has been ' . $this->status . '.')
            ->action('View Estimate', route('estimates.show', $this->estimate))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $actionBy = auth()->check() ? auth()->user()->name : 'the client';

        return [
            'estimate_id' => $this->estimate->id,
            'estimate_number' => $this->estimate->estimate_number,
            'status' => $this->status,
            'comments' => $this->comments,
            'message' => "Estimate #{$this->estimate->estimate_number} was {$this->status} by {$actionBy}.",
        ];
    }
}

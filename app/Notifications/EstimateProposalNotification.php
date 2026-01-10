<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Estimate;
use App\Models\User;

class EstimateProposalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $estimate;
    protected $proposer;

    /**
     * Create a new notification instance.
     */
    public function __construct(Estimate $estimate, User $proposer)
    {
        $this->estimate = $estimate;
        $this->proposer = $proposer;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Estimate Proposal: ' . $this->estimate->estimate_number)
            ->line($this->proposer->name . ' has proposed changes to Estimate #' . $this->estimate->estimate_number . '.')
            ->action('Review Proposal', route('estimates.show', $this->estimate))
            ->line('Please review the changes and approve them if appropriate.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'estimate_id' => $this->estimate->id,
            'estimate_number' => $this->estimate->estimate_number,
            'proposer_id' => $this->proposer->id,
            'proposer_name' => $this->proposer->name,
            'message' => $this->proposer->name . ' proposed changes (v' . $this->estimate->version . ').',
            'link' => route('estimates.show', $this->estimate),
        ];
    }
}

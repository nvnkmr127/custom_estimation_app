<?php

namespace App\Notifications;

use App\Models\Estimate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HotLeadAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public $estimate;
    public $details;

    /**
     * Create a new notification instance.
     */
    public function __construct(Estimate $estimate, array $details = [])
    {
        $this->estimate = $estimate;
        $this->details = $details;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $score = $this->estimate->engagement_score;
        $clientName = $this->estimate->client->name ?? 'Unknown Client';

        return (new MailMessage)
            ->error() // Uses red coloring for urgency
            ->subject("🔥 Hot Lead Alert: {$clientName} matched criteria!")
            ->line("The nurturing system detected a Hot Lead.")
            ->line("**Estimate:** #{$this->estimate->estimate_number}")
            ->line("**Client:** {$clientName}")
            ->line("**Engagement Score:** {$score}")
            ->line("**Reason:** " . ($this->details['reason'] ?? 'High velocity engagement detected.'))
            ->action('View Estimate Details', route('estimates.show', $this->estimate->id))
            ->line('It is recommended to call this client immediately.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'estimate_id' => $this->estimate->id,
            'message' => "Hot Lead Alert for Estimate #{$this->estimate->estimate_number}",
            'score' => $this->estimate->engagement_score
        ];
    }
}

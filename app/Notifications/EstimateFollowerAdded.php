<?php

namespace App\Notifications;

use App\Models\Estimate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EstimateFollowerAdded extends Notification implements ShouldQueue
{
    use Queueable;

    public $estimate;
    public $permissions;

    /**
     * Create a new notification instance.
     */
    public function __construct(Estimate $estimate, array $permissions = [])
    {
        $this->estimate = $estimate;
        $this->permissions = $permissions;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
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
        $perms = empty($this->permissions) ? 'view' : implode(', ', $this->permissions);

        return (new MailMessage)
            ->subject('You have been added to Estimate #' . $this->estimate->estimate_number)
            ->line('You have been added as a follower to Estimate #' . $this->estimate->estimate_number . '.')
            ->line('Permissions: ' . $perms)
            ->action('View Estimate', route('estimates.show', $this->estimate))
            ->line('Thank you!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'estimate_id' => $this->estimate->id,
            'estimate_number' => $this->estimate->estimate_number,
            'permissions' => $this->permissions,
            'message' => 'You were added as a follower to Estimate #' . $this->estimate->estimate_number,
            'link' => route('estimates.show', $this->estimate),
        ];
    }
}

<?php

namespace App\Notifications;

use App\Models\EstimateApproval;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApprovalTimeoutNotification extends Notification
{
    use Queueable;

    protected EstimateApproval $approval;

    public function __construct(EstimateApproval $approval)
    {
        $this->approval = $approval;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $estimate = $this->approval->estimate;
        $number = $estimate->estimate_number ?? ('#' . $this->approval->estimate_id);

        return (new MailMessage)
            ->subject('Approval overdue: Estimate ' . $number)
            ->line('An estimate approval assigned to you has passed its timeout and still needs your decision.')
            ->line('**Estimate:** ' . $number)
            ->action('Review Estimate', route('estimates.show', $this->approval->estimate_id))
            ->line('Please review it at your earliest convenience.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'approval_id' => $this->approval->id,
            'estimate_id' => $this->approval->estimate_id,
            'message' => 'An approval assigned to you is overdue and awaiting your decision.',
        ];
    }
}

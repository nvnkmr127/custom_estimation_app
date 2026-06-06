<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

class EstimateStatusChanged extends Notification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(
        private $estimate,
        private string $oldStatus
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['expo'];
    }

    /**
     * Send the Expo push notification.
     */
    public function toExpo($notifiable): array
    {
        $tokens = $notifiable->deviceTokens()->pluck('token')->toArray();

        if (empty($tokens)) {
            return [];
        }

        Http::post('https://exp.host/--/api/v2/push/send', [
            'to'    => $tokens,
            'title' => 'Estimate ' . $this->estimate->estimate_number,
            'body'  => 'Status changed to: ' . $this->estimate->status,
            'data'  => [
                'estimate_id' => $this->estimate->id
            ],
        ]);

        return [];
    }
}

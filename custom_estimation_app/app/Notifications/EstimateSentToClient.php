<?php

namespace App\Notifications;

use App\Models\Estimate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class EstimateSentToClient extends Notification implements ShouldQueue
{
    use Queueable;

    public $estimate;

    /**
     * Create a new notification instance.
     */
    public function __construct(Estimate $estimate)
    {
        $this->estimate = $estimate;
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
        $url = $this->estimate->public_url;
        $pixelUrl = route('tracking.pixel', $this->estimate->id);

        return (new MailMessage)
            ->subject('New Estimate from '.config('app.name').': #'.$this->estimate->estimate_number)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('We have prepared an estimate for your review.')
            ->line(new HtmlString('<strong>Estimate Number:</strong> #'.$this->estimate->estimate_number))
            ->line(new HtmlString('<strong>Total Amount:</strong> '.number_format($this->estimate->grand_total, 2).' '.$this->estimate->currency))
            ->action('View & Sign Estimate', $url)
            ->line('If you have any questions, please don\'t hesitate to contact us.')
            ->line('Thank you for your business!')
            ->line(new HtmlString('<img src="'.$pixelUrl.'" width="1" height="1" style="display:none !important;" />'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'estimate_id' => $this->estimate->id,
            'estimate_number' => $this->estimate->estimate_number,
            'message' => 'Estimate #'.$this->estimate->estimate_number.' was sent to you.',
        ];
    }
}

<?php

namespace App\Notifications;

use App\Models\Estimate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class EstimateNurtureNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $estimate;
    public $contentData; // Array containing subject, greeting, lines, button text

    /**
     * Create a new notification instance.
     *
     * @param Estimate $estimate
     * @param array $contentData {
     *     'subject' => string,
     *     'body_lines' => array<string>,
     *     'button_text' => string, // Optional, defaults to "View Estimate"
     * }
     */
    public function __construct(Estimate $estimate, array $contentData)
    {
        $this->estimate = $estimate;
        $this->contentData = $contentData;
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

        $subject = $this->replaceTokens($this->contentData['subject'] ?? 'Update on your Estimate #{{ estimate_number }}');

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting('Hello ' . $notifiable->name . ',');

        $lines = $this->contentData['body_lines'] ?? ['We noticed you haven\'t had a chance to look at the estimate yet.'];

        foreach ($lines as $line) {
            $mail->line($this->replaceTokens($line));
        }

        $buttonText = $this->contentData['button_text'] ?? 'View Estimate';

        $mail->action($buttonText, $url)
            ->line('If you have any questions, simply reply to this email.')
            ->line(new HtmlString('<img src="' . $pixelUrl . '" width="1" height="1" style="display:none !important;" />'));

        return $mail;
    }

    protected function replaceTokens($text)
    {
        if (empty($text))
            return '';

        $expiryDate = $this->estimate->expiry_date;
        $diffDays = $expiryDate ? (int) now()->startOfDay()->diffInDays($expiryDate->startOfDay(), false) : 0;
        // If expired, diffDays is negative.

        $map = [
            '{{ client_name }}' => $this->estimate->client->name ?? 'Valued Client',
            '{{ client_first_name }}' => explode(' ', $this->estimate->client->name ?? 'Client')[0],
            '{{ estimate_number }}' => $this->estimate->estimate_number,
            '{{ estimate_total }}' => number_format($this->estimate->grand_total, 2),
            '{{ estimate_expiry }}' => $expiryDate ? $expiryDate->format('M d, Y') : 'N/A',
            '{{ days_until_expiry }}' => max(0, $diffDays),
            '{{ company_name }}' => config('app.name'),
        ];

        return str_replace(array_keys($map), array_values($map), $text);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'estimate_id' => $this->estimate->id,
            'estimate_number' => $this->estimate->estimate_number,
            'message' => $this->contentData['subject'] ?? 'Automated follow-up sent.',
            'type' => 'nurture_followup'
        ];
    }
}

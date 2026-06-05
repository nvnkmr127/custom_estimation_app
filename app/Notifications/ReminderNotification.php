<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReminderNotification extends Notification
{
    use Queueable;

    protected $reminder;

    /**
     * Create a new notification instance.
     */
    public function __construct(\App\Models\Reminder $reminder)
    {
        $this->reminder = $reminder;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];
        $type = $this->reminder->type;

        if ($type === 'email' || $type === 'both') {
            $channels[] = 'mail';
        }
        if ($type === 'in_app' || $type === 'both') {
            $channels[] = 'database';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reminder: '.$this->reminder->title)
            ->line('You have a scheduled reminder.')
            ->line('**Title:** '.$this->reminder->title)
            ->line('**Description:** '.($this->reminder->description ?? 'No description provided.'))
            ->action('View Reminder', route('reminders.index'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'reminder_id' => $this->reminder->id,
            'title' => $this->reminder->title,
            'message' => $this->reminder->description,
            'remindable_type' => $this->reminder->remindable_type,
            'remindable_id' => $this->reminder->remindable_id,
        ];
    }
}

<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class NotificationPoller extends Component
{
    public $lastPolledAt;

    public function mount()
    {
        $this->lastPolledAt = now()->toDateTimeString();
    }

    public function poll()
    {
        if (!Auth::check())
            return;

        $user = Auth::user();

        $notifications = $user->unreadNotifications()
            ->where('created_at', '>', $this->lastPolledAt)
            ->get();

        foreach ($notifications as $notification) {
            $data = $notification->data;
            $message = $data['message'] ?? $data['subject'] ?? 'New activity detected';

            $this->dispatch('notify', [
                'message' => $message,
                'type' => 'success',
                'sound' => true
            ]);
        }

        $this->lastPolledAt = now()->toDateTimeString();
    }

    public function render()
    {
        return <<<'HTML'
            <div wire:poll.15s="poll" class="hidden"></div>
        HTML;
    }
}

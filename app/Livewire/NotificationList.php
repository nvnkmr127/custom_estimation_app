<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class NotificationList extends Component
{
    use WithPagination;

    protected function currentUser(): User
    {
        return User::query()->findOrFail(Auth::id());
    }

    public function markAsRead(string $notificationId): void
    {
        $user = $this->currentUser();

        $notification = $user->notifications()->whereKey($notificationId)->firstOrFail();
        $notification->markAsRead();
    }

    public function markAllAsRead(): void
    {
        $user = $this->currentUser();

        $user->unreadNotifications->markAsRead();
    }

    public function render()
    {
        $user = $this->currentUser();

        return view('livewire.notification-list', [
            'notifications' => $user->notifications()->latest()->paginate(25),
        ]);
    }
}

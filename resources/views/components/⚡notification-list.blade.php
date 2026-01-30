<?php

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

new class extends Component
{
    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        
        $this->dispatch('notification-updated');
    }

    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        $this->dispatch('notification-updated');
    }

    public function navigateTo($url, $id)
    {
        $this->markAsRead($id);
        return redirect()->to($url);
    }

    public function with()
    {
        $notifications = Auth::user()->notifications()->orderBy('created_at', 'desc')->get();

        $grouped = $notifications->groupBy(function ($notification) {
            $date = $notification->created_at;
            if ($date->isToday()) {
                return 'Today';
            } elseif ($date->isYesterday()) {
                return 'Yesterday';
            } else {
                return 'Older';
            }
        });

        return [
            'groupedNotifications' => $grouped,
        ];
    }
}; ?>

<div class="space-y-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-lg font-semibold text-gray-900">Notifications</h2>
        @if(Auth::user()->unreadNotifications->isNotEmpty())
            <button wire:click="markAllAsRead" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 transition-colors">
                Mark all as read
            </button>
        @endif
    </div>

    @forelse($groupedNotifications as $group => $notifications)
        <div class="space-y-4">
            <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider px-2">{{ $group }}</h3>
            <div class="bg-white shadow-sm ring-1 ring-gray-200 rounded-xl divide-y divide-gray-100 overflow-hidden">
                @foreach($notifications as $notification)
                    @php
                        $data = $notification->data;
                        $urgency = data_get($data, 'urgency', 'normal');
                        $type = $notification->type;
                        
                        $borderColor = match($urgency) {
                            'critical' => 'border-red-500',
                            'high' => 'border-orange-500',
                            'normal' => 'border-blue-500',
                            'low' => 'border-gray-300',
                            default => 'border-transparent'
                        };

                        $icon = match($type) {
                            'approval.requested' => 'heroicon-o-check-badge',
                            'comment.added' => 'heroicon-o-chat-bubble-left-right',
                            'estimate.viewed' => 'heroicon-o-eye',
                            'user.registered' => 'heroicon-o-user-plus',
                            default => 'heroicon-o-bell'
                        };

                        $iconColor = match($urgency) {
                            'critical' => 'text-red-600',
                            'high' => 'text-orange-600',
                            default => 'text-indigo-600'
                        };
                    @endphp

                    <div class="relative group flex items-start p-4 hover:bg-gray-50 transition-colors duration-200 {{ $notification->unread() ? 'bg-indigo-50/20' : '' }} border-l-4 {{ $borderColor }}">
                        <div class="flex-shrink-0 mt-1">
                            <span class="inline-flex items-center justify-center h-10 w-10 rounded-lg bg-white ring-1 ring-gray-200 shadow-sm">
                                <x-dynamic-component :component="$icon" class="h-6 w-6 {{ $iconColor }}" />
                            </span>
                        </div>

                        <div class="ml-4 flex-1 min-w-0">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">
                                        {{ data_get($data, 'subject') ?? data_get($data, 'message') ?? 'New Notification' }}
                                    </p>
                                    <p class="text-sm text-gray-600 mt-0.5 line-clamp-2">
                                        {{ data_get($data, 'comment_content') ?? data_get($data, 'content_snippet') ?? 'Click to see details' }}
                                    </p>
                                </div>
                                <span class="text-xs text-gray-400 whitespace-nowrap ml-4">
                                    {{ $notification->created_at->diffForHumans() }}
                                </span>
                            </div>

                            <div class="mt-3 flex items-center gap-3">
                                {{-- Inline CTAs --}}
                                @if(isset($data['estimate_id']))
                                    <button wire:click="navigateTo('{{ route('estimates.show', $data['estimate_id']) }}', '{{ $notification->id }}')"
                                       class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-white bg-indigo-600 rounded-md hover:bg-indigo-700 shadow-sm transition-all transform hover:scale-105">
                                        <x-heroicon-m-eye class="h-3.5 w-3.5 mr-1.5" />
                                        View Estimate
                                    </button>
                                @endif

                                @if($type === 'approval.requested')
                                    <button wire:click="navigateTo('{{ route('estimates.show', $data['estimate_id']) }}#approvals', '{{ $notification->id }}')"
                                       class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-indigo-700 bg-indigo-50 border border-indigo-100 rounded-md hover:bg-indigo-100 transition-all">
                                        <x-heroicon-m-check class="h-3.5 w-3.5 mr-1.5" />
                                        Approve
                                    </button>
                                @endif

                                @if($type === 'comment.added')
                                    <button wire:click="navigateTo('{{ route('estimates.show', $data['estimate_id']) }}#comments', '{{ $notification->id }}')"
                                       class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-gray-700 bg-gray-50 border border-gray-200 rounded-md hover:bg-gray-100 transition-all">
                                        <x-heroicon-m-arrow-uturn-left class="h-3.5 w-3.5 mr-1.5" />
                                        Reply
                                    </button>
                                @endif

                                @if($notification->unread())
                                    <button wire:click="markAsRead('{{ $notification->id }}')" 
                                            class="text-xs font-medium text-gray-400 hover:text-gray-600 transition-colors">
                                        Dismiss
                                    </button>
                                @endif
                            </div>
                        </div>

                        {{-- Unread Dot --}}
                        @if($notification->unread())
                            <div class="absolute top-4 right-4 h-2 w-2 rounded-full bg-indigo-600 shadow-[0_0_8px_rgba(79,70,229,0.5)]"></div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="text-center py-20 bg-white rounded-2xl border-2 border-dashed border-gray-200">
            <div class="inline-flex items-center justify-center h-16 w-16 rounded-full bg-gray-50 mb-4">
                <x-heroicon-o-bell-slash class="h-8 w-8 text-gray-400" />
            </div>
            <h3 class="text-base font-semibold text-gray-900">All caught up!</h3>
            <p class="text-sm text-gray-500 mt-1">You don't have any notifications right now.</p>
        </div>
    @endforelse
</div>
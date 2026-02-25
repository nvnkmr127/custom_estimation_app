<?php

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public $panelOpen = false;

    protected $listeners = [
        'notify' => '$refresh',
        'notification-updated' => '$refresh',
    ];

    public function mount()
    {
        // Handle broadcast listener if possible
    }

    public function markAsRead($id)
    {
        $user = Auth::user();
        if (!$user) return;
        
        $notification = $user->notifications()->findOrFail($id);
        $notification->markAsRead();
        
        $this->dispatch('notification-updated');
    }

    public function markAllAsRead()
    {
        $user = Auth::user();
        if (!$user) return;
        
        $user->unreadNotifications->markAsRead();
        $this->dispatch('notification-updated');
    }

    public function togglePanel()
    {
        $this->panelOpen = !$this->panelOpen;
    }

    public function with()
    {
        $user = Auth::user();
        $unreadCount = $user ? $user->unreadNotifications()->count() : 0;
        $latestNotifications = $user ? $user->unreadNotifications()->latest()->take(10)->get() : collect();

        return [
            'unreadCount' => $unreadCount,
            'latestNotifications' => $latestNotifications,
        ];
    }
}; ?>

<div x-data="{ 
    panelOpen: @entangle('panelOpen')
}" 
class="relative"
x-init="setInterval(() => { $wire.$refresh() }, 30000)">
    @auth
        <!-- Floating Bell Icon -->
        <div class="fixed bottom-6 right-6 z-[100]">
            <button 
                @click="togglePanel()"
                type="button"
                :class="{ 'shake-animation': !panelOpen && {{ $unreadCount > 0 ? 'true' : 'false' }} }"
                class="group relative flex h-14 w-14 items-center justify-center rounded-full bg-slate-900 text-white shadow-[0_10px_25px_-5px_rgba(0,0,0,0.3)] transition-all hover:bg-slate-800 hover:scale-110 focus:outline-none focus:ring-4 focus:ring-slate-300 active:scale-95"
                id="notification-bell-btn"
            >
                <svg class="h-6 w-6 text-slate-300 group-hover:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                </svg>

                <!-- Badge -->
                @if($unreadCount > 0)
                    <span 
                        class="absolute -top-0.5 -right-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-indigo-500 text-[9px] font-black text-white ring-2 ring-slate-900 shadow-lg"
                    >
                        {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                    </span>
                @endif
            </button>
        </div>

        <!-- Bottom Slide-up Panel Overlay -->
        <div 
            x-show="panelOpen" 
            x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-y-full opacity-0"
            x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-y-0 opacity-100"
            x-transition:leave-end="translate-y-full opacity-0"
            class="fixed inset-0 z-[110] flex items-end justify-center sm:justify-end px-4 pb-24 sm:px-6"
        >
            <!-- Backdrop for the panel -->
            <div x-show="panelOpen" @click="panelOpen = false" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity duration-300" x-transition.opacity></div>

            <div class="relative w-full max-w-lg overflow-hidden rounded-[2rem] bg-white shadow-[0_30px_60px_-15px_rgba(0,0,0,0.3)] ring-1 ring-slate-200">
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-slate-50 bg-slate-50/50 px-8 py-6">
                    <div>
                        <h3 class="text-lg font-black text-slate-900 tracking-tight">Notifications</h3>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">
                            {{ $unreadCount > 0 ? "$unreadCount Unread Updates" : 'All Clear' }}
                        </p>
                    </div>
                    <div class="flex items-center gap-4">
                        @if($unreadCount > 0)
                            <button wire:click="markAllAsRead" class="text-xs font-black text-indigo-600 hover:text-indigo-700 transition-colors uppercase tracking-wider">
                                Mark all read
                            </button>
                        @endif
                        <button @click="panelOpen = false" class="p-2 rounded-full hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-all">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- List -->
                <div class="max-h-[70vh] overflow-y-auto bg-white scrollbar-thin scrollbar-thumb-slate-200">
                    @forelse($latestNotifications as $notification)
                        @php
                            $data = $notification->data;
                            $iconColor = 'text-slate-400';
                            $link = data_get($data, 'link', '#');
                            $type = $notification->type;
                            $message = data_get($data, 'message', 'Notification received');
                            $subject = data_get($data, 'subject');
                            
                            $icon = 'heroicon-o-bell';
                            if ($type === 'App\Notifications\EstimateCommentNotification' || $type === 'comment.added') {
                                $iconColor = 'text-blue-500';
                                $icon = 'heroicon-o-chat-bubble-left-right';
                            } elseif ($type === 'App\Notifications\EstimateProposalNotification' || $type === 'approval.requested') {
                                $iconColor = 'text-emerald-500';
                                $icon = 'heroicon-o-check-badge';
                            } elseif ($type === 'App\Notifications\ReminderNotification') {
                                $iconColor = 'text-amber-500';
                                $icon = 'heroicon-o-clock';
                            }
                        @endphp
                        <div class="relative flex items-start gap-4 border-b border-slate-50 px-8 py-6 hover:bg-slate-50/80 transition-all group {{ $notification->unread() ? 'bg-indigo-50/20' : '' }}">
                            <div class="flex-shrink-0 mt-1">
                                <div class="rounded-xl p-2.5 bg-slate-50 border border-slate-100 shadow-sm {{ $iconColor }} group-hover:scale-110 transition-transform">
                                    <x-dynamic-component :component="$icon" class="h-5 w-5" />
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <a href="{{ $link }}" wire:click="markAsRead('{{ $notification->id }}')" class="block focus:outline-none">
                                    <p class="text-sm font-bold text-slate-900 line-clamp-1 mb-0.5 group-hover:text-indigo-600 transition-colors">
                                        {{ data_get($data, 'subject') ?? data_get($data, 'message') ?? 'New Update' }}
                                    </p>
                                    <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">
                                        {{ data_get($data, 'comment_content') ?? data_get($data, 'content_snippet') ?? $message }}
                                    </p>
                                    <p class="text-[10px] text-slate-400 mt-2 font-black uppercase tracking-widest">{{ $notification->created_at->diffForHumans() }}</p>
                                </a>
                            </div>
                            <div class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button wire:click="markAsRead('{{ $notification->id }}')" class="rounded-full p-2 text-slate-300 hover:text-rose-500 hover:bg-rose-50 transition-all" title="Dismiss">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center py-20 px-8 text-center">
                            <div class="rounded-full bg-slate-50 p-6 mb-4 border border-slate-100">
                                <svg class="h-10 w-10 text-slate-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                </svg>
                            </div>
                            <h4 class="text-base font-black text-slate-900 tracking-tight">You're all caught up!</h4>
                            <p class="text-xs text-slate-400 mt-1 uppercase font-bold tracking-wider">No new messages at this time.</p>
                        </div>
                    @endforelse
                </div>

                <!-- Footer -->
                <div class="border-t border-slate-50 bg-slate-50/50 px-8 py-5">
                    <a href="{{ route('notifications.index') }}" class="flex items-center justify-center gap-2 text-xs font-black text-slate-900 hover:text-indigo-600 transition-colors uppercase tracking-[0.2em]">
                        Full Inbox
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    @endauth
</div>

<style>
    @keyframes bell-shake {
        0% { transform: rotate(0); }
        2% { transform: rotate(10deg); }
        4% { transform: rotate(-10deg); }
        6% { transform: rotate(10deg); }
        8% { transform: rotate(-10deg); }
        10% { transform: rotate(5deg); }
        12% { transform: rotate(-5deg); }
        15% { transform: rotate(0); }
        100% { transform: rotate(0); }
    }
    .shake-animation {
        animation: bell-shake 10s ease-in-out infinite;
        transform-origin: top center;
    }
</style>
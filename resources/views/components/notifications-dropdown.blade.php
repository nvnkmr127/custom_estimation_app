@php
    $unreadCount = auth()->user()->unreadNotifications->count();
    $latestNotifications = auth()->user()->unreadNotifications->take(5);
@endphp

<div x-data="{ open: false }" class="relative ml-3">
    <div>
        <button @click="open = !open" type="button"
            class="relative flex rounded-full bg-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 hover:text-gray-500 text-gray-400"
            id="notification-menu-button" aria-expanded="false" aria-haspopup="true">
            <span class="sr-only">Open notification menu</span>
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
            </svg>
            @if($unreadCount > 0)
                <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-400 ring-2 ring-white"></span>
            @endif
        </button>
    </div>

    <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute right-0 z-10 mt-2 w-80 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
        role="menu" aria-orientation="vertical" aria-labelledby="notification-menu-button" tabindex="-1">
        <div class="px-4 py-2 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-sm font-semibold text-gray-900">Notifications</h3>
            @if($unreadCount > 0)
                <form action="{{ route('notifications.readAll') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-500">Mark all read</button>
                </form>
            @endif
        </div>

        <div class="max-h-96 overflow-y-auto">
            @forelse($latestNotifications as $notification)
                @php
                    $icon = 'heroicon-o-bell';
                    $iconColor = 'text-gray-400';
                    $link = data_get($notification->data, 'link', '#');
                    $type = $notification->type;

                    if ($type === 'App\Notifications\EstimateCommentNotification') {
                        $icon = 'heroicon-o-chat-bubble-left-ellipsis';
                        $iconColor = 'text-blue-500';
                    } elseif ($type === 'App\Notifications\EstimateProposalNotification') {
                        $icon = 'heroicon-o-document-text';
                        $iconColor = 'text-orange-500';
                    } elseif ($type === 'App\Notifications\ReminderNotification') {
                        $icon = 'heroicon-o-clock';
                        $iconColor = 'text-yellow-500';
                    }
                @endphp
                <div class="px-4 py-3 border-b border-gray-50 last:border-0 hover:bg-gray-50 flex items-start group">
                    <div class="flex-shrink-0 mr-3 mt-1">
                        @if($type === 'App\Notifications\EstimateCommentNotification')
                            <svg class="h-5 w-5 {{ $iconColor }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                            </svg>
                        @elseif($type === 'App\Notifications\EstimateProposalNotification')
                            <svg class="h-5 w-5 {{ $iconColor }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                        @elseif($type === 'App\Notifications\ReminderNotification')
                            <svg class="h-5 w-5 {{ $iconColor }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @else
                            <svg class="h-5 w-5 {{ $iconColor }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                            </svg>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <a href="{{ $link }}" class="focus:outline-none">
                            <span class="absolute inset-0" aria-hidden="true"></span>
                            <p class="text-xs text-gray-900 font-medium">
                                {{ data_get($notification->data, 'message', 'Notification received') }}
                            </p>
                            <p class="text-[10px] text-gray-500 mt-1">
                                {{ $notification->created_at->diffForHumans() }}
                            </p>
                        </a>
                    </div>
                    <form action="{{ route('notifications.read', $notification->id) }}" method="POST" class="relative z-10">
                        @csrf
                        <button type="submit"
                            class="ml-2 text-gray-300 hover:text-gray-500 p-1 rounded-full hover:bg-gray-100">
                            <span class="sr-only">Mark as read</span>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </form>
                </div>
            @empty
                <div class="px-4 py-6 text-center text-sm text-gray-500">
                    No unread notifications
                </div>
            @endforelse
        </div>

        <div class="px-4 py-2 border-t border-gray-100">
            <a href="{{ route('notifications.index') }}"
                class="block text-center text-xs text-indigo-600 hover:text-indigo-500 font-medium">
                View all notifications
            </a>
        </div>
    </div>
</div>
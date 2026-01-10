<x-app-layout>
    <div class="sm:flex sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Notifications</h1>
            <p class="mt-1 text-sm text-gray-500">Your recent activities and alerts</p>
        </div>
        <div class="mt-4 sm:mt-0 flex gap-3">
            <form action="{{ route('notifications.readAll') }}" method="POST">
                @csrf
                <button type="submit"
                    class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                    Mark All as Read
                </button>
            </form>
        </div>
    </div>

    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl overflow-hidden">
        <ul role="list" class="divide-y divide-gray-100">
            @forelse($notifications as $notification)
                @php
                    $icon = 'heroicon-o-bell';
                    $iconColor = 'text-gray-400';
                    $iconBg = 'bg-gray-100';
                    $link = data_get($notification->data, 'link', '#');
                    $type = $notification->type;

                    if ($type === 'App\Notifications\EstimateCommentNotification') {
                        $icon = 'heroicon-o-chat-bubble-left-ellipsis';
                        $iconColor = 'text-blue-600';
                        $iconBg = 'bg-blue-50';
                    } elseif ($type === 'App\Notifications\EstimateProposalNotification') {
                        $icon = 'heroicon-o-document-text';
                        $iconColor = 'text-orange-600';
                        $iconBg = 'bg-orange-50';
                    } elseif ($type === 'App\Notifications\ReminderNotification') {
                        $icon = 'heroicon-o-clock';
                        $iconColor = 'text-yellow-600';
                        $iconBg = 'bg-yellow-50';
                    }
                @endphp
                <li class="relative flex justify-between gap-x-6 px-4 py-5 hover:bg-gray-50 sm:px-6 {{ $notification->unread() ? 'bg-indigo-50/30' : '' }} group transition duration-150 ease-in-out">
                    <div class="flex min-w-0 gap-x-4">
                        <div class="h-12 w-12 flex-none rounded-full {{ $iconBg }} flex items-center justify-center">
                            @if($type === 'App\Notifications\EstimateCommentNotification')
                                <svg class="h-6 w-6 {{ $iconColor }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                                </svg>
                            @elseif($type === 'App\Notifications\EstimateProposalNotification')
                                <svg class="h-6 w-6 {{ $iconColor }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                            @elseif($type === 'App\Notifications\ReminderNotification')
                                <svg class="h-6 w-6 {{ $iconColor }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            @else
                                <svg class="h-6 w-6 {{ $iconColor }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                </svg>
                            @endif
                        </div>
                        <div class="min-w-0 flex-auto">
                            <a href="{{ $link }}" class="focus:outline-none">
                                <span class="absolute inset-0" aria-hidden="true"></span>
                                <p class="text-sm font-semibold leading-6 text-gray-900">
                                    {{ data_get($notification->data, 'message', 'Notification received') }}
                                </p>
                                <p class="mt-1 flex text-xs leading-5 text-gray-500">
                                    {{ $notification->created_at->diffForHumans() }}
                                </p>
                            </a>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-x-4 z-10">
                        @if($notification->unread())
                            <form action="{{ route('notifications.read', $notification->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="text-xs font-medium text-indigo-600 hover:text-indigo-500 hover:underline">
                                    Mark as read
                                </button>
                            </form>
                        @else
                           <span class="text-xs text-gray-400">Read</span> 
                        @endif
            @empty
                <li class="px-4 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <h3 class="mt-2 text-sm font-semibold text-gray-900">No notifications</h3>
                    <p class="mt-1 text-sm text-gray-500">You're all caught up!</p>
                </li>
            @endforelse
        </ul>
    </div>

    <div class="mt-6">
        {{ $notifications->links() }}
    </div>
</x-app-layout>
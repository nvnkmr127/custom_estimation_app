<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-lg font-semibold text-slate-900">Notifications</h1>
        <button
            type="button"
            wire:click="markAllAsRead"
            class="text-sm font-medium text-indigo-600 hover:text-indigo-500"
        >
            Mark all as read
        </button>
    </div>

    <div class="divide-y divide-slate-200 rounded-lg border border-slate-200 bg-white">
        @forelse ($notifications as $notification)
            <div class="flex items-start justify-between gap-4 p-4">
                <div class="min-w-0">
                    <div class="text-sm text-slate-900">
                        {{ data_get($notification->data, 'message', 'Notification received') }}
                    </div>
                    <div class="mt-1 text-xs text-slate-500">
                        {{ $notification->created_at->diffForHumans() }}
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-3">
                    @php($link = data_get($notification->data, 'link'))
                    @if ($link)
                        <a class="text-sm font-medium text-slate-700 hover:text-slate-900" href="{{ $link }}">
                            View
                        </a>
                    @endif

                    @if (is_null($notification->read_at))
                        <button
                            type="button"
                            wire:click="markAsRead('{{ $notification->id }}')"
                            class="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                        >
                            Mark read
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="p-6 text-sm text-slate-600">No notifications.</div>
        @endforelse
    </div>

    <div>
        {{ $notifications->links() }}
    </div>
</div>


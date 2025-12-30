<x-app-layout>
    <div class="sm:flex sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Reminders</h1>
            <p class="mt-1 text-sm text-gray-500">Your scheduled reminders and notifications</p>
        </div>
    </div>

    <div class="bg-white shadow-sm overflow-hidden sm:rounded-lg">
        <ul role="list" class="divide-y divide-gray-200">
            @forelse($reminders as $reminder)
                <li class="px-4 py-4 sm:px-6 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex flex-col flex-1">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-gray-900">{{ $reminder->title }}</span>
                                @if($reminder->is_sent)
                                    <span
                                        class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">Sent</span>
                                @else
                                    <span
                                        class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">Pending</span>
                                @endif
                            </div>
                            @if($reminder->description)
                                <p class="text-sm text-gray-600 mt-1">{{ $reminder->description }}</p>
                            @endif
                            <div class="mt-2 flex items-center gap-4 text-xs text-gray-500">
                                <span class="flex items-center gap-1">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ $reminder->remind_at->format('M d, Y H:i') }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                    {{ class_basename($reminder->remindable_type) }}
                                </span>
                            </div>
                        </div>
                        <div class="ml-4 flex-shrink-0 flex items-center gap-2">
                            @if(!$reminder->is_sent)
                                <form action="{{ route('reminders.read', $reminder) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">Dismiss</button>
                                </form>
                            @endif
                            <form action="{{ route('reminders.destroy', $reminder) }}" method="POST"
                                onsubmit="return confirm('Are you sure?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </li>
            @empty
                <li class="px-4 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <h3 class="mt-2 text-sm font-semibold text-gray-900">No reminders</h3>
                    <p class="mt-1 text-sm text-gray-500">You don't have any scheduled reminders.</p>
                </li>
            @endforelse
        </ul>

        @if($reminders->hasPages())
            <div class="px-4 py-4 sm:px-6 border-t border-gray-200">
                {{ $reminders->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
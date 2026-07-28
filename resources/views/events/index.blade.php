<x-app-layout>
    <div class="sm:flex sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">System Event Logs</h1>
            <p class="mt-1 text-sm text-gray-500">Immutable audit trail of all domain events.</p>
        </div>
        <div class="mt-4 sm:ml-4 sm:mt-0 sm:flex-none">
            <a href="{{ route('log-viewer.index') }}" target="_blank"
                class="inline-flex items-center gap-x-2 rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                <x-heroicon-o-document-magnifying-glass class="h-5 w-5" />
                Open Log Viewer
            </a>
        </div>
    </div>

    <!-- Quick Stats / Grouping -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Top Events (Last 7 Days)</h3>
            <div class="space-y-3">
                @foreach($summary as $item)
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700">{{ $item->event_name }}</span>
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800">
                            {{ $item->count }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Resource Distribution</h3>
            <div class="flex flex-wrap gap-4">
                @foreach($typeDistribution as $item)
                    <div class="flex flex-col p-4 bg-gray-50 rounded-lg min-w-[120px]">
                        <span class="text-xs text-gray-500 uppercase">{{ $item->entity_type ?? 'Generic' }}</span>
                        <span class="text-2xl font-bold text-gray-900">{{ $item->count }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" action="{{ route('event-logs.index') }}">
            <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-7 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Event Name</label>
                    <input type="text" name="event_name" value="{{ request('event_name') }}"
                        placeholder="e.g. estimate.created"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Entity Type</label>
                    <select name="entity_type"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">All Entities</option>
                        <option value="estimate" {{ request('entity_type') == 'estimate' ? 'selected' : '' }}>Estimate
                        </option>
                        <option value="user" {{ request('entity_type') == 'user' ? 'selected' : '' }}>User</option>
                        <option value="approval" {{ request('entity_type') == 'approval' ? 'selected' : '' }}>Approval
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Entity ID</label>
                    <input type="text" name="entity_id" value="{{ request('entity_id') }}"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">All Statuses</option>
                        <option value="logged" {{ request('status') == 'logged' ? 'selected' : '' }}>Logged</option>
                        <option value="processed" {{ request('status') == 'processed' ? 'selected' : '' }}>Processed
                        </option>
                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit"
                        class="flex-1 inline-flex justify-center items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        Filter
                    </button>
                    <a href="{{ route('event-logs.index') }}"
                        class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                        Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Event List -->
    <div class="bg-white shadow-sm overflow-hidden sm:rounded-lg">
        <ul role="list" class="divide-y divide-gray-200">
            @forelse($events as $event)
                <li class="px-4 py-4 sm:px-6 hover:bg-gray-50 transition">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                {{ $event->status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                                    {{ $event->status }}
                                </span>
                                <a href="{{ route('event-logs.show', $event) }}"
                                    class="text-sm font-bold text-indigo-600 truncate hover:underline">
                                    {{ $event->event_name }}
                                </a>
                            </div>
                            <div class="flex items-center gap-4 text-xs text-gray-500">
                                <span>ID: <span class="font-mono text-gray-700">{{ $event->event_id }}</span></span>
                                <span>Entity: <span
                                        class="font-semibold">{{ $event->entity_type }}:{{ $event->entity_id }}</span></span>
                                <span>Source: {{ $event->source }}</span>
                            </div>
                        </div>
                        <div class="flex flex-col items-end text-right ml-4">
                            <time datetime="{{ $event->created_at }}" class="text-sm text-gray-900 font-medium">
                                {{ $event->created_at->format('M d, Y H:i:s') }}
                            </time>
                            <span class="text-xs text-gray-500 mt-1">
                                Triggered by User: #{{ $event->triggered_by ?? 'System' }}
                            </span>
                            <a href="{{ route('event-logs.show', $event) }}"
                                class="mt-2 text-xs font-medium text-indigo-600 hover:text-indigo-900">
                                Inspect Payload &rarr;
                            </a>
                        </div>
                    </div>
                </li>
            @empty
                <li class="px-4 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <p class="mt-2 text-sm text-gray-500">No event logs found.</p>
                </li>
            @endforelse
        </ul>

        @if($events->hasPages())
            <div class="px-4 py-4 sm:px-6 bg-gray-50 border-t border-gray-200">
                {{ $events->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('event-logs.index') }}" class="text-gray-500 hover:text-gray-700">
                    &larr; Back to Logs
                </a>
                <h1 class="text-2xl font-bold text-gray-900">Event Details</h1>
            </div>
            <div class="flex gap-2">
                {{-- Future: Replay Button --}}
                <button disabled
                    class="opacity-50 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none">
                    Replay Event (Coming Soon)
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Meta Information -->
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Meta Data</h3>
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Event ID (UUID)</dt>
                            <dd class="mt-1 text-sm font-mono text-gray-900 break-all">{{ $eventLog->event_id }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Event Name</dt>
                            <dd class="mt-1 text-sm font-semibold text-indigo-600">{{ $eventLog->event_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Class</dt>
                            <dd class="mt-1 text-xs font-mono text-gray-600 break-all">{{ $eventLog->event_class }}</dd>
                        </div>
                        <div class="pt-4 border-t border-gray-200">
                            <dt class="text-sm font-medium text-gray-500">Occurred At</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $eventLog->created_at->format('M d, Y H:i:s') }}
                            </dd>
                            <dd class="text-xs text-gray-500">{{ $eventLog->created_at->diffForHumans() }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Context</h3>
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Entity</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ ucfirst($eventLog->entity_type) }} #{{ $eventLog->entity_id }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Triggered By</dt>
                            <dd class="mt-1 text-sm text-gray-900">User ID: {{ $eventLog->triggered_by ?? 'System' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Source</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $eventLog->source }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Processing Status</dt>
                            <dd class="mt-1">
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $eventLog->status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                                    {{ ucfirst($eventLog->status) }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Payload Inspector -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Payload -->
                <!-- Payload -->
                <div class="bg-white shadow rounded-lg px-6 py-4">
                    <details class="group" open>
                        <summary class="flex justify-between items-center cursor-pointer list-none">
                            <h3 class="text-lg font-medium text-gray-900">Event Payload</h3>
                            <span class="transition group-open:rotate-180">
                                <svg fill="none" class="w-6 h-6" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg">
                                    <polyline points="6 9 12 15 18 9"/>
                                </svg>
                            </span>
                        </summary>
                        <div class="mt-4 bg-gray-50 rounded-lg p-4 border border-gray-200 overflow-x-auto">
                            <pre class="text-xs text-gray-800 font-mono">@json($maskedPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)</pre>
                        </div>
                    </details>
                </div>

                <!-- Listener Execution -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Listener Execution</h3>
                    @if($eventLog->listeners->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th
                                            class="px-3 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Listener</th>
                                        <th
                                            class="px-3 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status</th>
                                        <th
                                            class="px-3 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Attempts</th>
                                        <th
                                            class="px-3 py-3.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Message</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($eventLog->listeners as $listener)
                                                            <tr>
                                                                <td class="px-3 py-3.5 text-sm font-medium text-gray-900 break-all">
                                                                    {{ class_basename($listener->listener_class) }}
                                                                    <span
                                                                        class="block text-xs text-gray-500 font-normal">{{ $listener->listener_class }}</span>
                                                                </td>
                                                                <td class="px-3 py-3.5 text-sm">
                                                                    <span
                                                                        class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset
                                                                                {{ $listener->status === 'success' ? 'bg-green-50 text-green-700 ring-green-600/20' :
                                        ($listener->status === 'failed' ? 'bg-red-50 text-red-700 ring-red-600/10' : 'bg-gray-50 text-gray-600 ring-gray-500/10') }}">
                                                                        {{ ucfirst($listener->status) }}
                                                                    </span>
                                                                </td>
                                                                <td class="px-3 py-3.5 text-sm text-gray-500">{{ $listener->attempts }}</td>
                                                                <td class="px-3 py-3.5 text-sm text-gray-500 max-w-xs truncate"
                                                                    title="{{ $listener->error_message }}">
                                                                    {{ $listener->error_message ?? '-' }}
                                                                </td>
                                                            </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-gray-500 italic">No listener execution logs found. Listeners may be pending
                            dispatch or not yet processed.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
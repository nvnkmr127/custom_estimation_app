<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8 flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center gap-x-3">
                {{ __('Event Log') }}
                <span
                    class="rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">#{{ $eventLog->id }}</span>
            </h2>
            <div class="flex items-center gap-4">
                {{-- Future: Replay Button --}}
                <a href="{{ route('event-logs.index') }}"
                    class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                    &larr; Back to Logs
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-8">

                <!-- Event Class & Context -->
                <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-base font-semibold leading-7 text-gray-900">Event Details</h3>
                            <p class="mt-1 text-sm leading-6 text-gray-500">Execution context and metadata.</p>
                        </div>
                        <span
                            class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $eventLog->status === 'failed' ? 'bg-red-50 text-red-700 ring-red-600/10' : 'bg-green-50 text-green-700 ring-green-600/20' }}">
                            {{ ucfirst($eventLog->status) }}
                        </span>
                    </div>

                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-6 border-t border-gray-100 pt-6">
                        <div class="col-span-1 sm:col-span-2">
                            <dt class="text-sm font-medium leading-6 text-gray-500">Event Class</dt>
                            <dd
                                class="mt-1 text-sm leading-6 text-gray-900 font-mono bg-gray-50 px-2 py-1 rounded inline-block break-all">
                                {{ $eventLog->event_class }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium leading-6 text-gray-500">Event Name</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-900 font-semibold text-indigo-600">
                                {{ $eventLog->event_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium leading-6 text-gray-500">Occurred At</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-900">
                                <span
                                    title="{{ $eventLog->created_at }}">{{ $eventLog->created_at->format('M d, Y H:i:s') }}</span>
                                <span
                                    class="text-xs text-gray-500 ml-1">({{ $eventLog->created_at->diffForHumans() }})</span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium leading-6 text-gray-500">Entity</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-900">
                                {{ ucfirst($eventLog->entity_type) }} #{{ $eventLog->entity_id }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium leading-6 text-gray-500">Triggered By</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-900">User ID:
                                {{ $eventLog->triggered_by ?? 'System' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium leading-6 text-gray-500">Source</dt>
                            <dd class="mt-1 text-sm leading-6 text-gray-900">{{ $eventLog->source }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium leading-6 text-gray-500">Event UUID</dt>
                            <dd class="mt-1 text-xs leading-6 text-gray-500 font-mono break-all">
                                {{ $eventLog->event_id }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Payload Card -->
                <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl overflow-hidden">
                    <div class="border-b border-gray-200 bg-gray-50 px-6 py-4 flex items-center justify-between">
                        <h3 class="text-base font-semibold leading-6 text-gray-900">Event Payload</h3>
                        <div class="flex items-center space-x-2">
                            <span class="text-xs text-gray-500 uppercase font-bold tracking-wider">JSON</span>
                        </div>
                    </div>
                    <div class="bg-gray-900 p-0 overflow-x-auto">
                        <pre
                            class="p-6 text-sm font-mono text-gray-300 leading-relaxed overflow-x-auto whitespace-pre">@json($maskedPayload ?? $eventLog->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)</pre>
                    </div>
                </div>
            </div>

            <!-- Sidebar / Listeners -->
            <div class="lg:col-span-1">
                <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl p-6 sticky top-6">
                    <h3 class="text-base font-semibold leading-6 text-gray-900 mb-6 flex items-center justify-between">
                        Listener Execution
                        <span
                            class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10">{{ $eventLog->listeners->count() }}
                            Listeners</span>
                    </h3>

                    @if($eventLog->listeners->count() > 0)
                        <div class="flow-root">
                            <ul role="list" class="-mb-8">
                                @foreach($eventLog->listeners as $listener)
                                    <li>
                                        <div class="relative pb-8">
                                            @if(!$loop->last)
                                                <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"
                                                    aria-hidden="true"></span>
                                            @endif
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span
                                                        class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white {{ $listener->status === 'success' ? 'bg-green-500' : ($listener->status === 'failed' ? 'bg-red-500' : 'bg-gray-400') }}">
                                                        <svg class="h-4 w-4 text-white" fill="none" socket="currentColor"
                                                            viewBox="0 0 24 24">
                                                            @if($listener->status === 'success')
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M5 13l4 4L19 7" />
                                                            @elseif($listener->status === 'failed')
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                            @else
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            @endif
                                                        </svg>
                                                    </span>
                                                </div>
                                                <div class="flex-1 min-w-0 pt-1.5 space-y-1">
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900">
                                                            {{ class_basename($listener->listener_class) }}
                                                        </p>
                                                        <p class="text-xs text-gray-500 break-all font-mono">
                                                            {{ $listener->listener_class }}
                                                        </p>
                                                    </div>

                                                    @if($listener->error_message)
                                                        <div class="rounded-md bg-red-50 p-2 border border-red-100 mt-2">
                                                            <p class="text-xs text-red-700 break-words font-mono">
                                                                {{ Str::limit($listener->error_message, 150) }}
                                                            </p>
                                                        </div>
                                                    @endif

                                                    <div class="flex items-center text-xs text-gray-400 mt-1 space-x-2">
                                                        <span>{{ $listener->attempts }} attempts</span>
                                                        <span>&middot;</span>
                                                        <span
                                                            class="{{ $listener->status === 'success' ? 'text-green-600' : ($listener->status === 'failed' ? 'text-red-600' : 'text-gray-600') }}">
                                                            {{ ucfirst($listener->status) }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <div class="text-center py-6 bg-gray-50 rounded-lg border border-dashed border-gray-300">
                            <p class="text-sm text-gray-500 italic">No listeners logged.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
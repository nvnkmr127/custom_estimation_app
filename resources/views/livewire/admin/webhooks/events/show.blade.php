<div>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-8">

            <!-- Header / Metadata Summary -->
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-base font-semibold leading-7 text-gray-900">Event Information</h2>
                        <p class="mt-1 text-sm leading-6 text-gray-500">Details and metadata for this webhook event.</p>
                    </div>
                    <span
                        class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                        ID: {{ $event->id }}
                    </span>
                </div>

                <dl
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-x-8 gap-y-6 border-t border-gray-100 pt-6">
                    <div>
                        <dt class="text-sm font-medium leading-6 text-gray-500">Event Type</dt>
                        <dd
                            class="mt-1 text-sm leading-6 text-gray-900 font-mono bg-gray-50 px-2 py-0.5 rounded inline-block">
                            {{ $event->event_type }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium leading-6 text-gray-500">Occurred At</dt>
                        <dd class="mt-1 text-sm leading-6 text-gray-900">
                            <span title="{{ $event->occurred_at }}">{{ $event->occurred_at->diffForHumans() }}</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium leading-6 text-gray-500">Idempotency Key</dt>
                        <dd class="mt-1 text-xs leading-6 text-gray-500 font-mono break-all">
                            {{ $event->idempotency_key }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium leading-6 text-gray-500">Trace ID</dt>
                        <dd class="mt-1 text-xs leading-6 text-gray-500 font-mono break-all">
                            {{ $event->payload['meta']['trace_id'] ?? 'N/A' }}</dd>
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
                        class="p-6 text-sm font-mono text-gray-300 leading-relaxed overflow-x-auto whitespace-pre">{{ json_encode($event->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            </div>
        </div>

        <!-- Sidebar / Timeline -->
        <div class="lg:col-span-1">
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl p-6 sticky top-6">
                <h3 class="text-base font-semibold leading-6 text-gray-900 mb-6 flex items-center justify-between">
                    Delivery Timeline
                    <span
                        class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10">{{ $event->deliveries->count() }}
                        Attempts</span>
                </h3>

                <div class="flow-root">
                    <ul role="list" class="-mb-8">
                        @forelse($event->deliveries as $delivery)
                            <li>
                                <div class="relative pb-8">
                                    @if(!$loop->last)
                                        <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"
                                            aria-hidden="true"></span>
                                    @endif
                                    <div class="relative flex space-x-3 group cursor-pointer"
                                        wire:click="selectDelivery('{{ $delivery->id }}')">
                                        <div>
                                            <span
                                                class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white {{ $delivery->status === 'success' ? 'bg-green-500' : ($delivery->status === 'failed' ? 'bg-red-500' : 'bg-amber-500') }} group-hover:scale-110 transition-transform duration-200 shadow-sm">
                                                <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    @if($delivery->status === 'success')
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M5 13l4 4L19 7" />
                                                    @elseif($delivery->status === 'failed')
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M6 18L18 6M6 6l12 12" />
                                                    @else
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    @endif
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="flex-1 min-w-0 pt-1.5 flex justify-between space-x-4">
                                            <div>
                                                <p
                                                    class="text-sm font-medium text-gray-900 group-hover:text-indigo-600 transition-colors">
                                                    {{ $delivery->webhook->name ?? 'Unknown Endpoint' }}
                                                </p>
                                                @if($delivery->error_message)
                                                    <p class="mt-0.5 text-xs text-red-600 font-medium truncate max-w-[12rem]">
                                                        {{ Str::limit($delivery->error_message, 40) }}
                                                    </p>
                                                @endif
                                                <div class="mt-1 flex items-center gap-x-2 text-xs text-gray-500">
                                                    <span class="font-mono">#{{ $delivery->attempt }}</span>
                                                    <span>&middot;</span>
                                                    <span>{{ $delivery->duration_ms }}ms</span>
                                                    <span>&middot;</span>
                                                    <span
                                                        class="font-mono {{ str_starts_with((string) $delivery->response_status, '2') ? 'text-green-600' : (str_starts_with((string) $delivery->response_status, '5') || str_starts_with((string) $delivery->response_status, '4') ? 'text-red-600' : 'text-gray-600') }}">HTTP
                                                        {{ $delivery->response_status ?? '---' }}</span>
                                                </div>
                                            </div>
                                            <div class="text-right text-xs whitespace-nowrap text-gray-400 tabular-nums">
                                                {{ $delivery->updated_at->format('H:i:s') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="py-4 text-center text-sm text-gray-500 italic">No delivery attempts yet.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Delivery Details Modal -->
    <x-modal name="delivery-details" wire:model.live="selectedDeliveryId" maxWidth="3xl">
        @if($selectedDelivery)
            <div class="bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:w-full">
                <!-- Modal Header -->
                <div class="bg-gray-50 px-4 py-4 sm:px-6 border-b border-gray-100 flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <span
                            class="flex-shrink-0 inline-flex items-center justify-center h-8 w-8 rounded-full {{ $selectedDelivery->status === 'success' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' }}">
                            @if($selectedDelivery->status === 'success')
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            @else
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            @endif
                        </span>
                        <div>
                            <h3 class="text-base font-semibold leading-6 text-gray-900">
                                Delivery Attempt #{{ $selectedDelivery->attempt }}
                            </h3>
                            <p class="text-sm text-gray-500 truncate max-w-sm">
                                to <span
                                    class="font-medium text-gray-900">{{ $selectedDelivery->webhook->url ?? 'Unknown URL' }}</span>
                            </p>
                        </div>
                    </div>
                    <div class="text-right flex flex-col items-end">
                        <span
                            class="inline-flex items-center rounded-md bg-white px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 tabular-nums">
                            {{ $selectedDelivery->updated_at->format('M d, H:i:s') }}
                        </span>
                        <span class="text-xs text-gray-400 mt-1">{{ $selectedDelivery->duration_ms }}ms duration</span>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="px-4 py-5 sm:p-6 space-y-6 max-h-[70vh] overflow-y-auto">

                    @if($selectedDelivery->error_message)
                        <div class="rounded-md bg-red-50 p-4 border border-red-100">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">Delivery Error</h3>
                                    <div class="mt-2 text-sm text-red-700">
                                        <p>{{ $selectedDelivery->error_message }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Request Headers -->
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Request Headers</h4>
                        <div class="bg-gray-50 rounded-lg border border-gray-200 overflow-hidden">
                            <pre
                                class="p-4 text-xs font-mono text-gray-600 overflow-x-auto whitespace-pre">{{ json_encode($selectedDelivery->request_headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                    </div>

                    <!-- Response -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Response Body</h4>
                            <span
                                class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                                Status: {{ $selectedDelivery->response_status ?? 'N/A' }}
                            </span>
                        </div>
                        <div class="bg-gray-50 rounded-lg border border-gray-200 overflow-hidden relative group">
                            <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <!-- Placeholder for copy button if we had JS -->
                            </div>
                            @if(is_array($selectedDelivery->response_body) || is_object($selectedDelivery->response_body))
                                <pre
                                    class="p-4 text-xs font-mono text-gray-600 overflow-x-auto whitespace-pre">{{ json_encode($selectedDelivery->response_body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            @else
                                <pre
                                    class="p-4 text-xs font-mono text-gray-600 overflow-x-auto whitespace-pre-wrap break-words max-h-60">{{ Str::limit((string) $selectedDelivery->response_body, 2000) }}</pre>
                            @endif
                        </div>
                    </div>

                    @if($selectedDelivery->next_retry_at)
                        <div class="flex items-center justify-end text-sm text-gray-500 border-t border-gray-100 pt-4">
                            <span>Scheduled for retry: </span>
                            <span class="ml-1 font-medium text-gray-900">{{ $selectedDelivery->next_retry_at }}</span>
                        </div>
                    @endif
                </div>

                <!-- Modal Footer -->
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-100">
                    <button type="button" wire:click="closeDeliveryDetails"
                        class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 ring-1 ring-inset ring-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                        Close Details
                    </button>
                    <!-- Add Manual Retry Button Here later if needed -->
                </div>
            </div>
        @endif
    </x-modal>
</div>
```
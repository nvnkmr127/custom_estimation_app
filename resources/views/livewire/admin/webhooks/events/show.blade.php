<div>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Payload Card -->
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Event Payload</h3>
                <div class="bg-gray-900 rounded-md p-4 overflow-x-auto">
                    <pre
                        class="text-xs text-green-400 font-mono">{{ json_encode($event->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            </div>

            <!-- Metadata -->
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Metadata</h3>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Event Type</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $event->event_type }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Occurred At</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $event->occurred_at }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Idempotency Key</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $event->idempotency_key }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Trace ID</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $event->payload['meta']['trace_id'] ?? 'N/A' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Sidebar / Timeline -->
        <div class="lg:col-span-1">
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Delivery Timeline</h3>

                <div class="flow-root">
                    <ul role="list" class="-mb-8">
                        @foreach($event->deliveries as $delivery)
                            <li>
                                <div class="relative pb-8">
                                    @if(!$loop->last)
                                        <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"
                                            aria-hidden="true"></span>
                                    @endif
                                    <div class="relative flex space-x-3 cursor-pointer group"
                                        wire:click="selectDelivery('{{ $delivery->id }}')">
                                        <div>
                                            <span
                                                class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white {{ $delivery->status === 'success' ? 'bg-green-500' : ($delivery->status === 'failed' ? 'bg-red-500' : 'bg-gray-400') }} group-hover:ring-gray-100 transition">
                                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor"
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
                                        <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                            <div>
                                                <p class="text-sm text-gray-500">
                                                    Sent to <span
                                                        class="font-medium text-gray-900">{{ $delivery->webhook->name ?? 'Unknown Endpoint' }}</span>
                                                </p>
                                                @if($delivery->error_message)
                                                    <p class="mt-1 text-xs text-red-600 font-medium">
                                                        {{ Str::limit($delivery->error_message, 50) }}
                                                    </p>
                                                @endif
                                                <div class="mt-1 text-xs text-gray-400">
                                                    Attempt #{{ $delivery->attempt }} • {{ $delivery->duration_ms }}ms •
                                                    HTTP {{ $delivery->response_status ?? 'N/A' }}
                                                </div>
                                            </div>
                                            <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                                <time
                                                    datetime="{{ $delivery->updated_at }}">{{ $delivery->updated_at->format('H:i:s') }}</time>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Delivery Details Modal -->
    <x-modal name="delivery-details" wire:model.live="selectedDeliveryId" maxWidth="2xl">
        @if($selectedDelivery)
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div
                        class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full {{ $selectedDelivery->status === 'success' ? 'bg-green-100' : 'bg-red-100' }} sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 {{ $selectedDelivery->status === 'success' ? 'text-green-600' : 'text-red-600' }}"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            @if($selectedDelivery->status === 'success')
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            @else
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            @endif
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Delivery Attempt #{{ $selectedDelivery->attempt }}
                        </h3>
                        <div class="mt-2 text-sm text-gray-500">
                            Sent to <span class="font-bold">{{ $selectedDelivery->webhook->url ?? 'Unknown URL' }}</span> at
                            {{ $selectedDelivery->updated_at }}
                        </div>

                        <div class="mt-4 border-t border-gray-200 pt-4 space-y-4 text-left">
                            @if($selectedDelivery->error_message)
                                <div class="bg-red-50 p-3 rounded-md">
                                    <h4 class="text-sm font-medium text-red-800">Error</h4>
                                    <p class="text-sm text-red-700 mt-1">{{ $selectedDelivery->error_message }}</p>
                                </div>
                            @endif

                            <div>
                                <h4 class="text-sm font-medium text-gray-900">Request Headers</h4>
                                <div class="mt-1 bg-gray-50 rounded-md p-2 overflow-x-auto">
                                    <pre
                                        class="text-xs text-gray-600 font-mono">{{ json_encode($selectedDelivery->request_headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                </div>
                            </div>

                            <div>
                                <h4 class="text-sm font-medium text-gray-900">Response
                                    ({{ $selectedDelivery->response_status ?? 'No Status' }})</h4>
                                <div class="mt-1 bg-gray-50 rounded-md p-2 overflow-x-auto">
                                    @if(is_array($selectedDelivery->response_body) || is_object($selectedDelivery->response_body))
                                        <pre
                                            class="text-xs text-gray-600 font-mono">{{ json_encode($selectedDelivery->response_body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    @else
                                        <pre
                                            class="text-xs text-gray-600 font-mono">{{ Str::limit((string) $selectedDelivery->response_body, 1000) }}</pre>
                                    @endif
                                </div>
                            </div>

                            <div class="flex justify-between text-xs text-gray-500 mt-2">
                                <span>Duration: {{ $selectedDelivery->duration_ms }}ms</span>
                                <span>Next Retry: {{ $selectedDelivery->next_retry_at ?? 'None' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" wire:click="closeDeliveryDetails"
                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Close
                </button>
            </div>
        @endif
    </x-modal>
</div>
```
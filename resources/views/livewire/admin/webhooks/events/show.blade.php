<div class="space-y-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <nav class="flex mb-2" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-2 text-xs font-semibold text-gray-400 uppercase tracking-widest">
                    <li><a href="{{ route('admin.webhooks.events.index') }}"
                            class="hover:text-indigo-600 transition-colors">Events</a></li>
                    <li><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M9 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" />
                        </svg></li>
                    <li class="text-gray-900">Event Details</li>
                </ol>
            </nav>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-x-3">
                {{ $event->event_type }}
                <span
                    class="px-2 py-1 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-lg border border-indigo-100 uppercase tracking-tighter">
                    {{ $event->id }}
                </span>
            </h1>
        </div>
        <div class="flex items-center gap-x-3">
            <button wire:click="replayEvent" wire:loading.attr="disabled"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-bold rounded-xl shadow-lg shadow-indigo-200 hover:bg-indigo-700 hover:shadow-indigo-300 transition-all active:scale-95 disabled:opacity-50 disabled:pointer-events-none">
                <svg wire:loading.remove wire:target="replayEvent" class="w-4 h-4 mr-2" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <svg wire:loading wire:target="replayEvent" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white"
                    fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                Replay Event
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Event Overview -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-50 bg-gray-50/50 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-widest">General Information</h3>
                    <div class="flex items-center space-x-2">
                        @php $status = $event->delivery_status; @endphp
                        <span
                            class="px-3 py-1 text-[11px] font-bold rounded-full uppercase tracking-tight
                            {{ $status === 'success' ? 'bg-green-100 text-green-700' : ($status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                            {{ $status }}
                        </span>
                    </div>
                </div>
                <div class="p-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Occurred At</p>
                        <p class="text-sm font-bold text-gray-900">{{ $event->occurred_at->format('M d, Y - H:i:s') }}
                        </p>
                        <p class="text-[10px] text-gray-400 mt-1 uppercase tracking-tighter">
                            {{ $event->occurred_at->diffForHumans() }}
                        </p>
                    </div>
                    <div class="md:col-span-1 lg:col-span-2">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Trace ID</p>
                        <p
                            class="text-sm font-mono text-gray-900 break-all bg-gray-50 p-2 rounded-xl border border-gray-100">
                            {{ $event->payload['metadata']['trace_id'] ?? 'N/A' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Idempotency Key
                        </p>
                        <p class="text-xs font-mono text-gray-500 truncate" title="{{ $event->idempotency_key }}">
                            {{ Str::limit($event->idempotency_key, 12) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Payload Section -->
            <div class="bg-[#1E1E2E] rounded-3xl shadow-xl overflow-hidden group">
                <div class="px-8 py-5 border-b border-white/5 flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="flex space-x-1.5">
                            <div class="w-2.5 h-2.5 rounded-full bg-[#FF5F56]"></div>
                            <div class="w-2.5 h-2.5 rounded-full bg-[#FFBD2E]"></div>
                            <div class="w-2.5 h-2.5 rounded-full bg-[#27C93F]"></div>
                        </div>
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest ml-2">JSON Payload</h3>
                    </div>
                    <button onclick="navigator.clipboard.writeText('{{ addslashes(json_encode($event->payload)) }}')"
                        class="p-2 text-gray-400 hover:text-white transition-colors bg-white/5 rounded-lg hover:bg-white/10 active:scale-95">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"
                                stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" />
                        </svg>
                    </button>
                </div>
                <div class="p-8 overflow-x-auto">
                    <pre
                        class="text-sm font-mono text-[#D1D1EB] leading-relaxed whitespace-pre">{{ json_encode($event->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            </div>
        </div>

        <!-- Sidebar / Delivery Timeline -->
        <div class="lg:col-span-1 space-y-6 sticky top-6">
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6">
                <h3
                    class="text-sm font-bold text-gray-900 uppercase tracking-widest mb-6 flex items-center justify-between">
                    Delivery Status
                    <span
                        class="bg-indigo-50 text-indigo-700 text-[10px] font-black px-2 py-0.5 rounded-lg border border-indigo-100">
                        {{ $event->deliveries->count() }} ATTEMPTS
                    </span>
                </h3>

                <!-- Matching Info -->
                @if($matchingSubscribers->isEmpty() && $potentialSubscribers->isNotEmpty())
                    <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-2xl">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 pt-0.5">
                                <svg class="h-5 w-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-xs font-bold text-amber-800 uppercase tracking-tight">Delivery Inactive</h3>
                                <p class="mt-1 text-xs text-amber-700">Matched {{ $potentialSubscribers->count() }}
                                    endpoint(s), but they are currently inactive. Activate them to resume delivery.</p>
                                <button wire:click="forceReplayEvent" class="mt-2 text-[10px] font-black uppercase text-amber-900 bg-amber-200/50 hover:bg-amber-200 px-2 py-1 rounded-lg transition-colors">
                                    Force Send to Inactive Endpoints &rarr;
                                </button>
                            </div>
                        </div>
                    </div>
                @elseif($matchingSubscribers->isEmpty())
                    <div class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded-2xl">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 pt-0.5">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-xs font-bold text-gray-800 uppercase tracking-tight">No Consumers</h3>
                                <p class="mt-1 text-xs text-gray-500 italic">No outbound endpoints are currently subscribed
                                    to this event type ({{ $event->event_type }}).</p>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="relative space-y-6">
                    @forelse($event->deliveries->sortByDesc('created_at') as $delivery)
                        <div class="relative pl-8 group cursor-pointer" wire:click="selectDelivery('{{ $delivery->id }}')">
                            <!-- Timeline node line -->
                            @if(!$loop->last)
                                <div
                                    class="absolute left-3.5 top-8 bottom-[-24px] w-px bg-gray-100 transition-colors group-hover:bg-indigo-200">
                                </div>
                            @endif
                            <!-- Node icon -->
                            <div
                                class="absolute left-0 top-0 w-7 h-7 rounded-full flex items-center justify-center transition-all duration-300 z-10 
                                            {{ $delivery->status === 'success' ? 'bg-green-100 text-green-600 group-hover:bg-green-600 group-hover:text-white' : ($delivery->status === 'failed' ? 'bg-red-100 text-red-600 group-hover:bg-red-600 group-hover:text-white' : 'bg-amber-100 text-amber-600 group-hover:bg-amber-600 group-hover:text-white') }}">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    @if($delivery->status === 'success')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                            d="M5 13l4 4L19 7" />
                                    @elseif($delivery->status === 'failed')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                            d="M6 18L18 6M6 6l12 12" />
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    @endif
                                </svg>
                            </div>

                            <div class="flex flex-col">
                                <div class="flex items-center justify-between">
                                    <span
                                        class="text-sm font-bold text-gray-900 group-hover:text-indigo-600 transition-colors">
                                        {{ $delivery->webhook->name ?? 'Unknown Endpoint' }}
                                    </span>
                                    <span
                                        class="text-[10px] font-mono text-gray-400 tabular-nums">{{ $delivery->updated_at->format('H:i:s') }}</span>
                                </div>
                                @if($delivery->error_message)
                                    <p class="text-[10px] text-red-500 font-bold mt-0.5 truncate uppercase tracking-tighter">
                                        {{ Str::limit($delivery->error_message, 30) }}
                                    </p>
                                @endif
                                <div class="mt-2 flex items-center gap-x-3">
                                    <div
                                        class="flex items-center space-x-1 px-1.5 py-0.5 bg-gray-50 rounded-lg border border-gray-100">
                                        <span class="text-[10px] font-black text-gray-400">#{{ $delivery->attempt }}</span>
                                        <span class="text-[10px] text-gray-400">&middot;</span>
                                        <span
                                            class="text-[10px] font-bold {{ str_starts_with((string) $delivery->response_status, '2') ? 'text-green-600' : 'text-red-500' }}">
                                            HTTP {{ $delivery->response_status ?? '---' }}
                                        </span>
                                    </div>
                                    <span
                                        class="text-[10px] font-medium text-gray-400">{{ $delivery->duration_ms }}ms</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="flex flex-col items-center py-8 text-center">
                            <div class="p-3 bg-gray-50 rounded-2xl mb-3">
                                <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
                                        stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" />
                                </svg>
                            </div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest italic">No attempts yet</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Delivery Details Modal -->
    <x-modal name="delivery-details" wire:model.live="selectedDeliveryId" maxWidth="3xl">
        @if($selectedDelivery)
            <div class="bg-white rounded-[2rem] overflow-hidden shadow-2xl transition-all border border-gray-100">
                <!-- Modal Header -->
                <div class="px-8 py-6 bg-gray-50/50 border-b border-gray-100 flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div
                            class="w-12 h-12 rounded-2xl flex items-center justify-center 
                                        {{ $selectedDelivery->status === 'success' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' }}">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                @if($selectedDelivery->status === 'success')
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                @else
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                        d="M6 18L18 6M6 6l12 12" />
                                @endif
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 leading-tight">Delivery Attempt
                                #{{ $selectedDelivery->attempt }}</h3>
                            <p class="text-sm font-medium text-gray-500 truncate max-w-sm">
                                {{ $selectedDelivery->webhook->url ?? 'No URL' }}
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <button wire:click="retryDelivery('{{ $selectedDelivery->id }}')"
                            class="inline-flex items-center px-4 py-2 bg-indigo-50 text-indigo-701 text-sm font-bold rounded-xl border border-indigo-100 hover:bg-indigo-600 hover:text-white transition-all active:scale-95 shadow-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                    stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" />
                            </svg>
                            Retry Now
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-8 space-y-8 max-h-[70vh] overflow-y-auto">
                    @if($selectedDelivery->error_message)
                        <div class="p-5 bg-red-50 rounded-2xl border border-red-100 flex items-start space-x-4">
                            <div class="p-2 bg-red-100 rounded-lg">
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                        stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-black text-red-800 uppercase tracking-widest">Error Message</h4>
                                <p class="text-sm text-red-700 mt-1 leading-relaxed">{{ $selectedDelivery->error_message }}</p>
                            </div>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Request Section -->
                        <div class="space-y-4">
                            <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Request Headers</h4>
                            <div class="bg-gray-900 rounded-2xl p-6 overflow-hidden">
                                <pre
                                    class="text-xs font-mono text-gray-300 leading-relaxed overflow-x-auto whitespace-pre">{{ json_encode($selectedDelivery->request_headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            </div>
                        </div>

                        <!-- Response Section -->
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Response Body
                                </h4>
                                <span class="bg-gray-100 text-gray-600 text-[10px] font-black px-2 py-0.5 rounded-lg">HTTP
                                    {{ $selectedDelivery->response_status ?? 'N/A' }}</span>
                            </div>
                            <div class="bg-gray-50 rounded-2xl p-6 border border-gray-100 overflow-hidden relative group">
                                @if(is_array($selectedDelivery->response_body) || is_object($selectedDelivery->response_body))
                                    <pre
                                        class="text-xs font-mono text-gray-500 leading-relaxed overflow-x-auto whitespace-pre">{{ json_encode($selectedDelivery->response_body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                @else
                                    <pre
                                        class="text-xs font-mono text-gray-500 leading-relaxed whitespace-pre-wrap break-words max-h-60">{{ Str::limit((string) $selectedDelivery->response_body, 2000) }}</pre>
                                @endif
                                <button
                                    onclick="navigator.clipboard.writeText('{{ addslashes((string) $selectedDelivery->response_body) }}')"
                                    class="absolute top-4 right-4 p-2 bg-white rounded-lg shadow-sm border border-gray-100 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path
                                            d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"
                                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    @if($selectedDelivery->next_retry_at)
                        <div class="p-6 bg-amber-50 rounded-2xl border border-amber-100 flex items-center justify-between">
                            <div class="flex items-center space-x-3 text-amber-800">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round"
                                        stroke-linejoin="round" stroke-width="2.5" />
                                </svg>
                                <span class="text-sm font-bold uppercase tracking-tight">Next Retry Scheduled</span>
                            </div>
                            <span
                                class="text-sm font-black text-amber-900 tabular-nums">{{ $selectedDelivery->next_retry_at->format('M d, H:i:s') }}</span>
                        </div>
                    @endif
                </div>

                <!-- Modal Footer -->
                <div class="px-8 py-6 bg-gray-50/50 border-t border-gray-100 flex justify-end">
                    <button type="button" wire:click="closeDeliveryDetails"
                        class="px-6 py-2.5 bg-white text-gray-700 text-sm font-bold rounded-xl border border-gray-200 hover:bg-gray-50 transition-all active:scale-95 shadow-sm">
                        Close Details
                    </button>
                </div>
            </div>
        @endif
    </x-modal>
</div>
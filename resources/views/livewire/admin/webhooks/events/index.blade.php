<div>
    <div class="space-y-6">
        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center space-x-4">
                <div class="p-3 bg-blue-50 rounded-xl">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Events</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total']) }}</p>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center space-x-4">
                <div class="p-3 bg-green-50 rounded-xl">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Success Rate</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['success_rate'] }}%</p>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center space-x-4">
                <div class="p-3 bg-red-50 rounded-xl">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Failed Delivery</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['failed']) }}</p>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center space-x-4">
                <div class="p-3 bg-amber-50 rounded-xl">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Processing</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['processing']) }}</p>
                </div>
            </div>
        </div>

        <!-- Filters & Search -->
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                <div class="md:col-span-5">
                    <label class="block text-xs font-semibold text-gray-400 uppercase mb-1 ml-1">Search</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input wire:model.live.debounce.300ms="search" type="text"
                            class="block w-full pl-10 pr-3 py-2 border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl text-sm transition-colors"
                            placeholder="ID, Trace ID or Payload content...">
                    </div>
                </div>

                <div class="md:col-span-3">
                    <label class="block text-xs font-semibold text-gray-400 uppercase mb-1 ml-1">Event Type</label>
                    <select wire:model.live="type"
                        class="block w-full border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl text-sm transition-colors">
                        <option value="">All Event Types</option>
                        @foreach($types as $t)
                            <option value="{{ $t }}">{{ $t }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-400 uppercase mb-1 ml-1">From</label>
                    <input wire:model.live="dateFrom" type="date"
                        class="block w-full border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl text-sm transition-colors">
                </div>

                <div class="md:col-span-2 flex space-x-2">
                    <div class="flex-1">
                        <label class="block text-xs font-semibold text-gray-400 uppercase mb-1 ml-1">To</label>
                        <input wire:model.live="dateTo" type="date"
                            class="block w-full border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl text-sm transition-colors">
                    </div>
                    @if($search || $type || $dateFrom || $dateTo)
                        <button wire:click="$set('search', ''); $set('type', ''); $set('dateFrom', ''); $set('dateTo', '');"
                            class="mb-0.5 p-2 text-gray-400 hover:text-red-500 transition-colors" title="Clear Filters">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead>
                        <tr class="bg-gray-50/50">
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-widest">
                                Event</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-widest">
                                Type</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-widest">
                                Occurred</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-widest">
                                Status</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-widest">
                                Source</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-gray-400 uppercase tracking-widest">
                                Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse ($events as $event)
                            <tr class="hover:bg-gray-50/80 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold text-gray-900 flex items-center">
                                            {{ Str::limit($event->id, 8) }}
                                            <button onclick="navigator.clipboard.writeText('{{ $event->id }}')"
                                                class="ml-1 opacity-0 group-hover:opacity-100 transition-opacity text-gray-400 hover:text-indigo-600">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path
                                                        d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"
                                                        stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
                                                </svg>
                                            </button>
                                        </span>
                                        <span class="text-[10px] font-mono text-gray-400 uppercase tracking-tighter">Trace:
                                            {{ $event->payload['meta']['trace_id'] ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-100">
                                        {{ $event->event_type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span
                                            class="text-sm text-gray-700">{{ $event->occurred_at->format('M d, Y') }}</span>
                                        <span
                                            class="text-xs text-gray-400 font-mono">{{ $event->occurred_at->format('H:i:s') }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    @php $status = $event->delivery_status; @endphp
                                    <div class="flex items-center space-x-2">
                                        @if($status === 'success')
                                            <span class="flex h-2 w-2 rounded-full bg-green-500"></span>
                                            <span
                                                class="text-xs font-bold text-green-700 uppercase tracking-tight">Delivered</span>
                                        @elseif($status === 'failed')
                                            <span class="flex h-2 w-2 rounded-full bg-red-500"></span>
                                            <span class="text-xs font-bold text-red-700 uppercase tracking-tight">Failed</span>
                                        @elseif($status === 'partial')
                                            <span class="flex h-2 w-2 rounded-full bg-amber-500"></span>
                                            <span
                                                class="text-xs font-bold text-amber-700 uppercase tracking-tight">Partial</span>
                                        @else
                                            <span class="flex h-2 w-2 rounded-full bg-blue-500 animate-pulse"></span>
                                            <span
                                                class="text-xs font-bold text-blue-700 uppercase tracking-tight">Processing</span>
                                        @endif
                                        <span
                                            class="text-[10px] text-gray-400 bg-gray-50 px-1.5 py-0.5 rounded border border-gray-100 font-mono">
                                            {{ $event->deliveries->where('status', 'success')->count() }}/{{ $event->deliveries->count() }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-xs text-gray-500 italic">
                                        {{ $event->payload['metadata']['source'] ?? 'system.internal' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('admin.webhooks.events.show', $event) }}"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-bold text-indigo-600 bg-indigo-50 border border-indigo-100 rounded-lg hover:bg-indigo-600 hover:text-white transition-all shadow-sm">
                                        Explore
                                        <svg class="ml-1.5 w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path d="M9 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round"
                                                stroke-width="2.5" />
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-20 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="p-4 bg-gray-50 rounded-full mb-4">
                                            <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                            </svg>
                                        </div>
                                        <p class="text-gray-900 font-bold">No events found</p>
                                        <p class="text-gray-400 text-sm mt-1">Try adjusting your filters or search terms.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($events->hasPages())
                <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100">
                    {{ $events->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
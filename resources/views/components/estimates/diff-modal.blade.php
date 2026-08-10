@props(['diff' => null, 'estimate' => null])

@if(!empty($diff))
<template x-teleport="body">
    <div x-show="showDiffModal" class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true" style="display: none;">
        <div x-show="showDiffModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity">
        </div>

        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div x-show="showDiffModal" @click.outside="showDiffModal = false"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative transform overflow-hidden rounded-xl bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:p-6">

                    <div class="absolute right-0 top-0 hidden pr-4 pt-4 sm:block">
                        <button type="button" @click="showDiffModal = false"
                            class="rounded-md bg-white text-slate-400 hover:text-slate-500 focus:outline-none">
                            <span class="sr-only">Close</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="sm:flex sm:items-start flex-col">
                        <!-- Sticky Header -->
                        <div class="w-full border-b border-slate-200 pb-4 mb-4 sticky top-0 bg-white z-10 pt-2">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-bold leading-6 text-slate-900" id="modal-title">
                                        Version Analysis
                                    </h3>
                                    <p class="text-sm text-slate-500 mt-1">
                                        Comparing
                                        <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10">v{{ $diff['summary']['new_version'] ?? ($estimate->version ?? 1) }}</span>
                                        &rarr;
                                        <span class="inline-flex items-center rounded-md bg-slate-50 px-2 py-0.5 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-500/10">v{{ $diff['summary']['old_version'] ?? (($estimate->version ?? 2) - 1) }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="w-full space-y-6 text-sm max-h-[70vh] overflow-y-auto pr-2 pb-6">

                            <!-- Net Financial Impact Card -->
                            @php
                                $netChange = $diff['summary']['net_change'] ?? 0;
                                $percentChange = $diff['summary']['percent_change'] ?? 0;
                                $oldTotalVal = $diff['summary']['old_grand_total'] ?? 0;
                                $newTotal = $diff['summary']['new_grand_total'] ?? 0;
                                $isPositive = $netChange > 0;
                                $isNegative = $netChange < 0;
                                $currency = $estimate->currency ?? 'USD';
                            @endphp

                            <div class="relative overflow-hidden rounded-xl border {{ $isPositive ? 'border-red-200 bg-red-50' : ($isNegative ? 'border-green-200 bg-green-50' : 'border-slate-200 bg-slate-50') }} p-4 shadow-sm">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">
                                            Net Financial Impact</p>
                                        <div class="mt-1 flex items-baseline gap-2">
                                            <span class="text-2xl font-bold tracking-tight {{ $isPositive ? 'text-red-700' : ($isNegative ? 'text-green-700' : 'text-slate-900') }} font-mono">
                                                {{ $netChange > 0 ? '+' : '' }}{{ $currency }} {{ number_format($netChange, 2) }}
                                            </span>
                                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $isPositive ? 'bg-red-100 text-red-800' : ($isNegative ? 'bg-green-100 text-green-800' : 'bg-slate-200 text-slate-800') }}">
                                                {{ number_format(abs($percentChange), 1) }}%
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-1">From
                                            {{ $currency }} {{ number_format($oldTotalVal, 2) }} to
                                            {{ $currency }} {{ number_format($newTotal, 2) }}
                                        </p>
                                    </div>
                                    <div class="rounded-full p-3 {{ $isPositive ? 'bg-red-100' : ($isNegative ? 'bg-green-100' : 'bg-slate-200') }}">
                                        @if($isPositive)
                                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
                                            </svg>
                                        @elseif($isNegative)
                                            <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6L9 12.75l4.286-4.286a11.948 11.948 0 0111.818 8.818" />
                                            </svg>
                                        @else
                                            <svg class="h-6 w-6 text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" />
                                            </svg>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Overview Changes -->
                            @if(!empty($diff['overview']))
                                <div>
                                    <div class="flex items-center gap-2 mb-3 pb-1 border-b border-slate-100">
                                        <svg class="h-5 w-5 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                        </svg>
                                        <h4 class="font-semibold text-slate-900">General Updates</h4>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        @foreach($diff['overview'] as $change)
                                            <div class="flex flex-col justify-between bg-white rounded-lg p-3 border border-slate-200 shadow-sm hover:border-indigo-300 transition-colors">
                                                <span class="text-xs font-semibold text-slate-500 uppercase">{{ $change['label'] }}</span>
                                                <div class="flex items-center gap-2 mt-2">
                                                    <span class="text-slate-400 line-through text-xs">{{ $change['is_currency'] ? number_format((float)$change['old'], 2) : $change['old'] }}</span>
                                                    <svg class="h-3 w-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                                    </svg>
                                                    <span class="font-bold text-slate-900 {{ $change['label'] === 'Grand Total' ? 'text-indigo-600' : '' }}">{{ $change['is_currency'] ? number_format((float)$change['new'], 2) : $change['new'] }}</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Added Items -->
                            @if(!empty($diff['items']['added']))
                                <div>
                                    <div class="flex items-center gap-2 mb-3 pb-1 border-b border-green-100">
                                        <div class="flex h-6 w-6 items-center justify-center rounded-full bg-green-100">
                                            <svg class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                            </svg>
                                        </div>
                                        <h4 class="font-semibold text-slate-900">Added Items</h4>
                                        <span class="ml-auto bg-green-100 text-green-700 py-0.5 px-2 rounded-full text-xs font-medium">
                                            {{ $diff['summary']['added_count'] ?? 0 }} {{ Str::plural('item', $diff['summary']['added_count'] ?? 0) }}
                                        </span>
                                    </div>
                                    @foreach($diff['items']['added'] as $section => $items)
                                        <div class="mb-4 last:mb-0">
                                            <h5 class="text-xs font-bold text-slate-400 uppercase mb-2 pl-1 tracking-wider">
                                                {{ $section }}
                                            </h5>
                                            <div class="space-y-2">
                                                @foreach($items as $item)
                                                    <div class="group flex items-start justify-between rounded-lg border border-green-100 bg-green-50/50 p-3 transition hover:bg-green-50">
                                                        <div class="pr-4">
                                                            <p class="font-medium text-slate-900">{{ $item->name }}</p>
                                                            <div class="mt-1 text-xs text-green-700">
                                                                <span class="font-mono font-medium">{{ $item->quantity }}</span>
                                                                &times; <span class="font-mono">{{ number_format($item->unit_price, 2) }}</span>
                                                            </div>
                                                        </div>
                                                        <div class="text-right">
                                                            <p class="font-bold text-green-700 font-mono">
                                                                +{{ number_format($item->total, 2) }}</p>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <!-- Modified Items -->
                            @if(!empty($diff['items']['modified']))
                                <div>
                                    <div class="flex items-center gap-2 mb-3 pb-1 border-b border-amber-100">
                                        <div class="flex h-6 w-6 items-center justify-center rounded-full bg-amber-100">
                                            <svg class="h-4 w-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
                                            </svg>
                                        </div>
                                        <h4 class="font-semibold text-slate-900">Modified Items</h4>
                                        <span class="ml-auto bg-amber-100 text-amber-700 py-0.5 px-2 rounded-full text-xs font-medium">
                                            {{ $diff['summary']['modified_count'] ?? 0 }} {{ Str::plural('item', $diff['summary']['modified_count'] ?? 0) }}
                                        </span>
                                    </div>
                                    @foreach($diff['items']['modified'] as $section => $modItems)
                                        <div class="mb-4 last:mb-0">
                                            <h5 class="text-xs font-bold text-slate-400 uppercase mb-2 pl-1 tracking-wider">
                                                {{ $section }}
                                            </h5>
                                            <div class="space-y-3">
                                                @foreach($modItems as $mod)
                                                    <div class="rounded-lg border border-amber-200 bg-white p-3 shadow-sm">
                                                        <div class="font-medium text-slate-900 border-b border-slate-100 pb-2 mb-2 flex items-center justify-between">
                                                            {{ $mod['item']->name }}
                                                            <span class="text-[10px] text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-100 font-bold">EDITED</span>
                                                        </div>
                                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                            @foreach($mod['changes'] as $change)
                                                                <div class="rounded bg-slate-50 p-2 text-xs border border-slate-100">
                                                                    <span class="block text-slate-500 font-medium mb-1">{{ $change['field'] }}</span>
                                                                    <div class="flex items-center gap-1.5 font-mono">
                                                                        <span class="line-through text-slate-400 decoration-slate-400/50">{{ is_numeric($change['old']) ? number_format((float)$change['old'], 2) : $change['old'] }}</span>
                                                                        <svg class="h-3 w-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                                                        </svg>
                                                                        <span class="font-bold text-amber-700">{{ is_numeric($change['new']) ? number_format((float)$change['new'], 2) : $change['new'] }}</span>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <!-- Removed Items -->
                            @if(!empty($diff['items']['removed']))
                                <div>
                                    <div class="flex items-center gap-2 mb-3 pb-1 border-b border-red-100">
                                        <div class="flex h-6 w-6 items-center justify-center rounded-full bg-red-100">
                                            <svg class="h-4 w-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                            </svg>
                                        </div>
                                        <h4 class="font-semibold text-slate-900">Removed Items</h4>
                                        <span class="ml-auto bg-red-100 text-red-700 py-0.5 px-2 rounded-full text-xs font-medium">
                                            {{ $diff['summary']['removed_count'] ?? 0 }} {{ Str::plural('item', $diff['summary']['removed_count'] ?? 0) }}
                                        </span>
                                    </div>
                                    @foreach($diff['items']['removed'] as $section => $items)
                                        <div class="mb-4 last:mb-0">
                                            <h5 class="text-xs font-bold text-slate-400 uppercase mb-2 pl-1 tracking-wider">
                                                {{ $section }}
                                            </h5>
                                            <div class="space-y-2">
                                                @foreach($items as $item)
                                                    <div class="flex items-center justify-between rounded-lg border border-red-100 bg-red-50/30 p-3 opacity-75 grayscale-[0.3]">
                                                        <div class="pr-4">
                                                            <p class="font-medium text-slate-900 line-through decoration-red-500/50">
                                                                {{ $item->name }}
                                                            </p>
                                                            <div class="mt-1 text-xs text-red-700">
                                                                <span class="font-mono font-medium">{{ $item->quantity }}</span>
                                                                &times; <span class="font-mono">{{ number_format($item->unit_price, 2) }}</span>
                                                            </div>
                                                        </div>
                                                        <div class="text-right">
                                                            <p class="font-bold text-red-700 font-mono line-through">
                                                                -{{ number_format($item->total, 2) }}</p>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                        </div>
                    </div>

                    <div class="mt-5 sm:mt-6">
                        <button type="button" @click="showDiffModal = false"
                            class="inline-flex w-full justify-center rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Close</button>
                    </div>

                </div>
            </div>
        </div>
    </div>
</template>
@endif
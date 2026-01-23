<div x-show="showDiffModal" class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true"
    style="display: none;">
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
                class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:p-6">

                <div class="absolute right-0 top-0 hidden pr-4 pt-4 sm:block">
                    <button type="button" @click="showDiffModal = false"
                        class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                        <h3 class="text-base font-semibold leading-6 text-gray-900" id="modal-title">Version
                            Changes</h3>
                        <p class="text-sm text-gray-500 mb-4">Comparing Version
                            {{ $estimate->version }} with Version {{ $estimate->version - 1 }}
                        </p>

                        <div class="space-y-6 text-sm max-h-[70vh] overflow-y-auto pr-2">
                            <!-- Overview Changes -->
                            @if(!empty($diff['overview']))
                                <div>
                                    <h4 class="font-medium text-slate-900 mb-2 border-b pb-1">General Updates
                                    </h4>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        @foreach($diff['overview'] as $change)
                                            <div
                                                class="bg-slate-50 rounded p-3 text-slate-900 text-xs shadow-sm ring-1 ring-slate-100">
                                                <span class="font-semibold block mb-1">{{ $change['label'] }}</span>
                                                <div class="flex items-center gap-2 text-slate-500">
                                                    <span
                                                        class="line-through">{{ $change['is_currency'] ? number_format($change['old'], 2) : $change['old'] }}</span>
                                                    <svg class="h-3 w-3 text-slate-400" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                                    </svg>
                                                    <span
                                                        class="font-bold text-slate-900">{{ $change['is_currency'] ? number_format($change['new'], 2) : $change['new'] }}</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Added Items -->
                            @if(!empty($diff['items']['added']))
                                <div>
                                    <h4 class="font-medium text-green-700 mb-2 border-b border-green-200 pb-1">
                                        Added Items</h4>
                                    <ul class="space-y-2">
                                        @foreach($diff['items']['added'] as $item)
                                            <li class="bg-green-50 rounded p-3 ring-1 ring-green-100">
                                                <div class="flex justify-between items-start">
                                                    <div>
                                                        <div class="font-medium text-green-900">
                                                            {{ $item->name }}
                                                        </div>
                                                        <div class="text-xs text-green-700">
                                                            {{ $item->section->name ?? 'General' }}
                                                        </div>
                                                    </div>
                                                    <div class="text-right text-xs">
                                                        <div class="font-semibold text-green-900">
                                                            {{ number_format($item->total, 2) }}
                                                        </div>
                                                        <div class="text-green-700">{{ $item->quantity }} x
                                                            {{ number_format($item->unit_price, 2) }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <!-- Modified Items -->
                            @if(!empty($diff['items']['modified']))
                                <div>
                                    <h4 class="font-medium text-amber-700 mb-2 border-b border-amber-200 pb-1">
                                        Modified Items</h4>
                                    <ul class="space-y-2">
                                        @foreach($diff['items']['modified'] as $mod)
                                            <li class="bg-amber-50 rounded p-3 ring-1 ring-amber-100">
                                                <div class="font-medium text-amber-900 mb-2">
                                                    {{ $mod['item']->name }} <span
                                                        class="text-xs font-normal text-amber-700">({{ $mod['item']->section->name ?? 'General' }})</span>
                                                </div>
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                    @foreach($mod['changes'] as $change)
                                                        <div class="bg-white/50 rounded p-1.5 text-xs">
                                                            <span class="text-amber-800 font-medium">{{ $change['field'] }}:</span>
                                                            <div class="flex items-center gap-1 mt-0.5">
                                                                <span
                                                                    class="line-through text-slate-500">{{ $change['old'] }}</span>
                                                                <span>&rarr;</span>
                                                                <span class="font-bold text-slate-900">{{ $change['new'] }}</span>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <!-- Removed Items -->
                            @if(!empty($diff['items']['removed']))
                                <div>
                                    <h4 class="font-medium text-red-700 mb-2 border-b border-red-200 pb-1">
                                        Removed Items</h4>
                                    <ul class="space-y-2">
                                        @foreach($diff['items']['removed'] as $item)
                                            <li class="bg-red-50 rounded p-3 ring-1 ring-red-100 opacity-75">
                                                <div class="flex justify-between items-start">
                                                    <div>
                                                        <div class="font-medium text-red-900 line-through">
                                                            {{ $item->name }}
                                                        </div>
                                                        <div class="text-xs text-red-700">
                                                            {{ $item->section->name ?? 'General' }}
                                                        </div>
                                                    </div>
                                                    <div class="text-right text-xs">
                                                        <div class="font-semibold text-red-900 line-through">
                                                            {{ number_format($item->total, 2) }}
                                                        </div>
                                                        <div class="text-red-700">{{ $item->quantity }} x
                                                            {{ number_format($item->unit_price, 2) }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="mt-5 sm:mt-6">
                    <button type="button" @click="showDiffModal = false"
                        class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
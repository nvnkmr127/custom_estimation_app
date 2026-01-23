<x-app-layout>
    <!-- Version Warning -->
    @if(!$estimate->is_current_version)
        <div class="mb-6 rounded-md bg-yellow-50 p-4 ring-1 ring-yellow-200">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">Archived Version</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p>You are viewing an older version of this estimate.
                            @php $currentVer = $allVersions->firstWhere('is_current_version', true); @endphp
                            @if($currentVer)
                                The <a href="{{ route('estimates.show', $currentVer) }}"
                                    class="font-medium underline hover:text-yellow-600">current version
                                    (v{{ $currentVer->version }})</a> is <strong>{{ ucfirst($currentVer->status) }}</strong>.
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Header & Toolbar -->
    <!-- Header & Toolbar -->
    <x-estimates.toolbar 
        :estimate="$estimate" 
        :checklists="$checklists ?? collect()" 
        :userApproval="$userApproval ?? false" 
        :declineReasons="$declineReasons ?? collect()" 
    />

    <!-- Quick Stats -->
    <x-estimates.quick-stats :estimate="$estimate" />

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Left Column: Items & Notes (3/4 width) -->
        <div class="lg:col-span-3 space-y-8">
            <!-- Items Table -->
            <div class="bg-white shadow-sm ring-1 ring-slate-200 sm:rounded-xl overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <h2 class="text-base font-semibold leading-7 text-slate-900 mb-6">
                        {{ $estimate->type === 'room_based' ? 'Rooms & Items' : 'Line Items' }}
                    </h2>

                    @if($estimate->type === 'room_based')
                        <div class="space-y-6">
                            @foreach($estimate->sections as $section)
                                <div class="border border-slate-200 rounded-lg overflow-hidden">
                                    <div
                                        class="bg-slate-50 px-4 py-2 border-b border-slate-200 font-medium text-sm text-slate-700">
                                        {{ $section->name }}
                                    </div>
                                    <table class="min-w-full divide-y divide-slate-200">
                                        <thead class="bg-slate-50/50">
                                            <tr>
                                                <th
                                                    class="px-3 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest w-16">
                                                    Image
                                                </th>
                                                <th
                                                    class="px-3 py-4 text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest w-40">
                                                    Unit Configuration</th>
                                                <th
                                                    class="px-3 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                                    Item Details</th>
                                                <th
                                                    class="px-3 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest w-28">
                                                    Size</th>
                                                <th
                                                    class="px-3 py-4 text-right text-[10px] font-bold text-slate-400 uppercase tracking-widest w-28">
                                                    Price</th>
                                                <th
                                                    class="px-3 py-4 text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest w-32">
                                                    Quantity</th>
                                                <th
                                                    class="px-3 py-4 text-right text-[10px] font-bold text-slate-400 uppercase tracking-widest w-32">
                                                    Total</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200 bg-white">
                                            @foreach($section->items as $item)
                                                <tr class="group hover:bg-slate-50/30 transition-colors">
                                                    <td class="px-3 py-4 align-middle">
                                                        @if($item->product && $item->product->images->isNotEmpty())
                                                            <div class="relative h-12 w-12 mx-auto">
                                                                <img src="{{ $item->product->images->first()->image_path }}"
                                                                    class="h-full w-full object-cover rounded-lg shadow-sm ring-1 ring-slate-200">
                                                            </div>
                                                        @else
                                                            <div
                                                                class="h-12 w-12 bg-slate-50 rounded-lg mx-auto flex items-center justify-center ring-1 ring-slate-200 border border-dashed border-slate-300">
                                                                <svg class="h-6 w-6 text-slate-300" fill="none" viewBox="0 0 24 24"
                                                                    stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
                                                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                                </svg>
                                                            </div>
                                                        @endif
                                                    </td>
                                                    <td
                                                        class="px-3 py-4 text-sm text-center align-middle border-b border-slate-100 last:border-0">
                                                        <div class="font-bold text-slate-900">
                                                            @if($item->unitType) {{ $item->unitType->name }} @endif
                                                        </div>
                                                        <div
                                                            class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">
                                                            {{ $item->unit_type }}
                                                        </div>
                                                    </td>
                                                    <td
                                                        class="px-3 py-4 text-sm text-slate-900 border-b border-slate-100 last:border-0">
                                                        <div class="min-w-0">
                                                            <div class="font-bold text-slate-900 mb-0.5">{{ $item->name }}</div>
                                                            @if($item->description)
                                                                <div class="text-xs text-slate-500 leading-relaxed max-w-sm mb-1.5">
                                                                    {{ $item->description }}
                                                                </div>
                                                            @endif
                                                            @if(!empty($item->options) && is_array($item->options))
                                                                <div class="flex flex-wrap gap-1.5">
                                                                    @foreach($item->options as $option)
                                                                        <span
                                                                            class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600 ring-1 ring-inset ring-slate-200">
                                                                            {{ $option['name'] }}: {{ $option['value'] }}
                                                                        </span>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                            @if($item->internal_note)
                                                                <div
                                                                    class="mt-2 text-[10px] text-amber-700 bg-amber-50 px-2 py-1 rounded-lg border border-amber-200 inline-flex items-center gap-1.5 font-medium shadow-xs">
                                                                    <svg class="h-3 w-3 text-amber-500" fill="none" viewBox="0 0 24 24"
                                                                        stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                                            stroke-width="2"
                                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                                    </svg>
                                                                    <span
                                                                        class="opacity-75 uppercase tracking-wider text-[9px] font-bold">Internal
                                                                        Note:</span> {{ $item->internal_note }}
                                                                </div>
                                                            @endif
                                                            <!-- Item Comment Button -->
                                                            <button
                                                                @click="openItemComments({{ $item->id }}, {{ Js::from($item->name) }}, {{ Js::from($item->comments->values()) }})"
                                                                type="button"
                                                                class="mt-2 inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-[10px] font-medium transition-colors
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        {{ $item->comments->isNotEmpty() ? 'bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-700/10' : 'text-slate-500 hover:bg-slate-100' }}">
                                                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24"
                                                                    stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
                                                                        d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                                                </svg>
                                                                @if($item->comments->isNotEmpty())
                                                                    {{ $item->comments->count() }}
                                                                    {{ Str::plural('Comment', $item->comments->count()) }}
                                                                @else
                                                                    Comment
                                                                @endif
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td
                                                        class="px-3 py-4 text-sm text-slate-900 border-b border-slate-100 last:border-0">
                                                        @if($item->length && $item->width)
                                                            <div class="flex flex-col gap-1.5">
                                                                <div class="flex items-center gap-2">
                                                                    <span
                                                                        class="text-[10px] font-bold text-slate-600 uppercase w-3">L</span>
                                                                    <span
                                                                        class="text-xs font-medium text-slate-900">{{ $item->length + 0 }}
                                                                        ft</span>
                                                                </div>
                                                                <div class="flex items-center gap-2">
                                                                    <span
                                                                        class="text-[10px] font-bold text-slate-600 uppercase w-3">W</span>
                                                                    <span
                                                                        class="text-xs font-medium text-slate-900">{{ $item->width + 0 }}
                                                                        ft</span>
                                                                </div>
                                                            </div>
                                                        @else
                                                            <span class="text-xs text-slate-400">-</span>
                                                        @endif
                                                    </td>
                                                    <td
                                                        class="px-3 py-4 text-sm text-right text-slate-600 font-medium align-middle border-b border-slate-100 last:border-0">
                                                        {{ $estimate->currency }} {{ number_format($item->unit_price, 2) }}
                                                    </td>
                                                    <td
                                                        class="px-3 py-4 text-sm text-center align-middle border-b border-slate-100 last:border-0">
                                                        <div class="font-bold text-slate-900">{{ $item->quantity }}</div>
                                                    </td>
                                                    <td
                                                        class="px-3 py-4 text-sm text-right font-bold text-slate-900 align-middle border-b border-slate-100 last:border-0">
                                                        {{ $estimate->currency }} {{ number_format($item->total, 2) }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="bg-slate-50">
                                            <tr>
                                                <td colspan="3" class="px-3 py-2 text-xs font-medium text-slate-500 text-right">
                                                    Room Total</td>
                                                <td class="px-3 py-2 text-xs font-bold text-slate-900 text-right">
                                                    {{ number_format($section->items->sum('total'), 2) }}
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <!-- Standard Items Table -->
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50/50">
                                <tr>
                                    <th
                                        class="px-3 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest w-16">
                                        Image
                                    </th>
                                    <th
                                        class="px-3 py-4 text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest w-40">
                                        Unit Configuration</th>
                                    <th
                                        class="px-3 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                        Item Details</th>
                                    <th
                                        class="px-3 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest w-28">
                                        Size</th>
                                    <th
                                        class="px-3 py-4 text-right text-[10px] font-bold text-slate-400 uppercase tracking-widest w-28">
                                        Price</th>
                                    <th
                                        class="px-3 py-4 text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest w-32">
                                        Quantity</th>
                                    <th
                                        class="px-3 py-4 text-right text-[10px] font-bold text-slate-400 uppercase tracking-widest w-32">
                                        Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                @foreach($estimate->items as $item)
                                                                            <tr class="group hover:bg-slate-50/30 transition-colors">
                                                                                <td class="px-3 py-4 align-middle">
                                                                                    @if($item->product && $item->product->images->isNotEmpty())
                                                                                        <div class="relative h-12 w-12 mx-auto">
                                                                                            <img src="{{ $item->product->images->first()->image_path }}"
                                                                                                class="h-full w-full object-cover rounded-lg shadow-sm ring-1 ring-slate-200">
                                                                                        </div>
                                                                                    @else
                                                                                        <div
                                                                                            class="h-12 w-12 bg-slate-50 rounded-lg mx-auto flex items-center justify-center ring-1 ring-slate-200 border border-dashed border-slate-300">
                                                                                            <svg class="h-6 w-6 text-slate-300" fill="none" viewBox="0 0 24 24"
                                                                                                stroke="currentColor">
                                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                                                            </svg>
                                                                                        </div>
                                                                                    @endif
                                                                                </td>
                                                                                <td
                                                                                    class="px-3 py-4 text-sm text-center align-middle border-b border-slate-100 last:border-0">
                                                                                    <div class="font-bold text-slate-900">
                                                                                        @if($item->unitType) {{ $item->unitType->name }} @endif
                                                                                    </div>
                                                                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">
                                                                                        {{ $item->unit_type }}
                                                                                    </div>
                                                                                </td>
                                                                                <td class="px-3 py-4 text-sm text-slate-900 border-b border-slate-100 last:border-0">
                                                                                    <div class="min-w-0">
                                                                                        <div class="font-bold text-slate-900 mb-0.5">{{ $item->name }}</div>
                                                                                        @if($item->description)
                                                                                            <div class="text-xs text-slate-500 leading-relaxed max-w-sm mb-1.5">
                                                                                                {{ $item->description }}
                                                                                            </div>
                                                                                        @endif
                                                                                        @if(!empty($item->options) && is_array($item->options))
                                                                                            <div class="flex flex-wrap gap-1.5">
                                                                                                @foreach($item->options as $option)
                                                                                                    <span
                                                                                                        class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600 ring-1 ring-inset ring-slate-200">
                                                                                                        {{ $option['name'] }}: {{ $option['value'] }}
                                                                                                    </span>
                                                                                                @endforeach
                                                                                            </div>
                                                                                        @endif
                                                                                        @if($item->internal_note)
                                                                                            <div
                                                                                                class="mt-2 text-[10px] text-amber-700 bg-amber-50 px-2 py-1 rounded-lg border border-amber-200 inline-flex items-center gap-1.5 font-medium shadow-xs">
                                                                                                <svg class="h-3 w-3 text-amber-500" fill="none" viewBox="0 0 24 24"
                                                                                                    stroke="currentColor">
                                                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                                                                </svg>
                                                                                                <span
                                                                                                    class="opacity-75 uppercase tracking-wider text-[9px] font-bold">Internal
                                                                                                    Note:</span> {{ $item->internal_note }}
                                                                                            </div>
                                                                                        @endif
                                                                                        <!-- Item Comment Button -->
                                                                                        <button @click="openItemComments({{ $item->id }}, {{ Js::from($item->name) }}, {{ Js::from($item->comments->map(function ($c) {
                                    $c->formatted_date = $c->created_at->format('M j, g:i A');
                                    return $c; })->values()) }})" type="button"
                                                                                            class="mt-2 inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-[10px] font-medium transition-colors
                                                                                                                                                                                                                                                                                                                                                                                                                                                        {{ $item->comments->isNotEmpty() ? 'bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-700/10' : 'text-slate-500 hover:bg-slate-100' }}">
                                                                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                                                    d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                                                                            </svg>
                                                                                            @if($item->comments->isNotEmpty())
                                                                                                {{ $item->comments->count() }}
                                                                                                {{ Str::plural('Comment', $item->comments->count()) }}
                                                                                            @else
                                                                                                Comment
                                                                                            @endif
                                                                                        </button>
                                                                                    </div>
                                                                                </td>
                                                                                <td class="px-3 py-4 text-sm text-slate-900 border-b border-slate-100 last:border-0">
                                                                                    @if($item->length && $item->width)
                                                                                        <div class="flex flex-col gap-1.5">
                                                                                            <div class="flex items-center gap-2">
                                                                                                <span class="text-[10px] font-bold text-slate-600 uppercase w-3">L</span>
                                                                                                <span class="text-xs font-medium text-slate-900">{{ $item->length + 0 }}
                                                                                                    ft</span>
                                                                                            </div>
                                                                                            <div class="flex items-center gap-2">
                                                                                                <span class="text-[10px] font-bold text-slate-600 uppercase w-3">W</span>
                                                                                                <span class="text-xs font-medium text-slate-900">{{ $item->width + 0 }}
                                                                                                    ft</span>
                                                                                            </div>
                                                                                        </div>
                                                                                    @else
                                                                                        <span class="text-xs text-slate-400">-</span>
                                                                                    @endif
                                                                                </td>
                                                                                <td
                                                                                    class="px-3 py-4 text-sm text-right text-slate-600 font-medium align-middle border-b border-slate-100 last:border-0">
                                                                                    {{ $estimate->currency }} {{ number_format($item->unit_price, 2) }}
                                                                                </td>
                                                                                <td
                                                                                    class="px-3 py-4 text-sm text-center align-middle border-b border-slate-100 last:border-0">
                                                                                    <div class="font-bold text-slate-900">{{ $item->quantity }}</div>
                                                                                </td>
                                                                                <td
                                                                                    class="px-3 py-4 text-sm text-right font-bold text-slate-900 align-middle border-b border-slate-100 last:border-0">
                                                                                    {{ $estimate->currency }} {{ number_format($item->total, 2) }}
                                                                                </td>
                                                                            </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif

                    <!-- Totals -->
                    <div class="mt-8 border-t border-slate-200 pt-6">
                        <dl class="space-y-3 text-sm max-w-xs ml-auto">
                            <div class="flex justify-between text-slate-600">
                                <dt>Subtotal</dt>
                                <dd class="font-medium">{{ $estimate->currency }}
                                    {{ number_format($estimate->subtotal, 2) }}
                                </dd>
                            </div>
                            @if($estimate->total_tax > 0)
                                <div class="flex justify-between text-slate-600">
                                    <dt>Tax</dt>
                                    <dd class="font-medium">{{ $estimate->currency }}
                                        {{ number_format($estimate->total_tax, 2) }}
                                    </dd>
                                </div>
                            @endif
                            @if($estimate->discount_total > 0)
                                <div class="flex justify-between text-red-600">
                                    <dt>Discount</dt>
                                    <dd class="font-medium">- {{ $estimate->currency }}
                                        {{ number_format($estimate->discount_total, 2) }}
                                    </dd>
                                </div>
                            @endif
                            <div
                                class="flex justify-between border-t border-slate-200 pt-3 text-base font-bold text-slate-900">
                                <dt>Grand Total</dt>
                                <dd>{{ $estimate->currency }} {{ number_format($estimate->grand_total, 2) }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            @if($estimate->client_note || $estimate->terms)
                <div class="bg-white shadow-sm ring-1 ring-slate-200 sm:rounded-xl px-4 py-5 sm:p-6">
                    <h3 class="text-base font-semibold text-slate-900 mb-4">Terms & Notes</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        @if($estimate->client_note)
                            <div>
                                <h4 class="text-sm font-medium text-slate-700 mb-2">Client Note</h4>
                                <p class="text-sm text-slate-500 leading-relaxed">{{ $estimate->client_note }}</p>
                            </div>
                        @endif
                        @if($estimate->terms)
                            <div>
                                <h4 class="text-sm font-medium text-slate-700 mb-2">Terms & Conditions</h4>
                                <p class="text-sm text-slate-500 leading-relaxed whitespace-pre-wrap">{{ $estimate->terms }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <!-- Right Column: Sidebar (1/3 width) -->
        <div class="space-y-6">

            <!-- Approval History -->
            @if($estimate->approvals->isNotEmpty())
                <div class="bg-white shadow-sm ring-1 ring-slate-200 sm:rounded-xl px-4 py-5 mb-6">
                    <h3 class="text-sm font-semibold text-slate-900 mb-3">Approval History</h3>
                    <ul class="space-y-4">
                        @foreach($estimate->approvals->sortBy('created_at') as $approval)
                            <li class="relative flex gap-x-4">
                                <div class="absolute left-0 top-0 flex w-6 justify-center -bottom-4">
                                    <div class="w-px bg-gray-200"></div>
                                </div>
                                <div class="relative flex h-6 w-6 flex-none items-center justify-center bg-white">
                                    @if($approval->status === 'approved')
                                        <div class="h-1.5 w-1.5 rounded-full bg-green-500 ring-1 ring-green-500"></div>
                                    @elseif($approval->status === 'rejected')
                                        <div class="h-1.5 w-1.5 rounded-full bg-red-500 ring-1 ring-red-500"></div>
                                    @elseif($approval->status === 'changes_requested')
                                        <div class="h-1.5 w-1.5 rounded-full bg-amber-500 ring-1 ring-amber-500"></div>
                                    @else
                                        <div class="h-1.5 w-1.5 rounded-full bg-gray-100 ring-1 ring-gray-300"></div>
                                    @endif
                                </div>
                                <div class="flex-auto py-0.5 text-xs leading-5 text-gray-500">
                                    <span class="font-medium text-gray-900">{{ $approval->user->name }}</span>
                                    <span class="block">{{ ucfirst(str_replace('_', ' ', $approval->status)) }}</span>
                                    <span class="block text-gray-400">{{ $approval->updated_at->format('M j, g:i A') }}</span>
                                    @if($approval->comments)
                                        <p class="mt-1 text-slate-600 bg-slate-50 p-2 rounded border border-slate-100 italic">
                                            "{{ $approval->comments }}"</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Internal Note -->
            @if($estimate->admin_note)
                <div class="bg-yellow-50 shadow-sm ring-1 ring-yellow-200 sm:rounded-xl px-4 py-5 mb-6">
                    <h3 class="text-sm font-semibold text-yellow-800 mb-2">Internal Note</h3>
                    <div class="text-sm text-yellow-700 whitespace-pre-wrap">{{ $estimate->admin_note }}</div>
                </div>
            @endif



            <!-- Comments Section -->
            <x-estimates.comments-section :estimate="$estimate" />

            <!-- Approval Chain -->
            <div class="bg-white shadow-sm ring-1 ring-slate-200 sm:rounded-xl px-4 py-5">
                <h3 class="text-sm font-semibold text-slate-900 mb-3">Client Details</h3>
                <div class="flex items-start gap-3">
                    <div
                        class="h-10 w-10 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600 font-bold shrink-0">
                        {{ substr($estimate->client->name ?? 'C', 0, 1) }}
                    </div>
                    <div>
                        <div class="font-medium text-slate-900">{{ $estimate->client->name ?? 'Unknown Client' }}</div>
                        <div class="text-sm text-slate-500">{{ $estimate->client->email ?? '' }}</div>
                        <div class="text-sm text-slate-500">{{ $estimate->client->phone ?? '' }}</div>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-slate-100 text-xs text-slate-400">
                    Estimate Date: {{ $estimate->estimate_date->format('M d, Y') }}
                </div>
            </div>

            <!-- Created By -->
            <!-- ... existing Created By ... -->

            <!-- Notification: Newer Proposal Available -->

            @if($latestVersion && $latestVersion->version > $estimate->version)
                <div class="bg-blue-50 shadow-sm ring-1 ring-blue-200 sm:rounded-xl px-4 py-3 mb-6">
                    <h3 class="text-xs font-bold text-blue-800 uppercase tracking-wider mb-2">Newer Version Available</h3>
                    <p class="text-xs text-blue-700 mb-3">There is a newer draft/proposal (v{{ $latestVersion->version }})
                        available.</p>
                    <a href="{{ route('estimates.show', $latestVersion) }}"
                        class="inline-block rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                        View v{{ $latestVersion->version }}
                    </a>
                </div>
            @endif

            <!-- Approval / Make Current (For Creator/Admin) -->
            @if(isset($estimate->parent) && $estimate->is_current_version === false && (auth()->id() === $estimate->created_by || auth()->user()->hasRole(['super_admin', 'admin'])))
                <div class="bg-yellow-50 shadow-sm ring-1 ring-yellow-200 sm:rounded-xl px-4 py-3 mb-6">
                    <h3 class="text-xs font-bold text-yellow-800 uppercase tracking-wider mb-2">Proposed Changes
                        (v{{ $estimate->version }})</h3>
                    <p class="text-xs text-yellow-700 mb-3">This is a proposed version. Approve it to make it the live
                        version.</p>

                    <form action="{{ route('estimates.approve-version', $estimate) }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="w-full rounded-md bg-yellow-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-yellow-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-yellow-600">
                            Approve & Make Live
                        </button>
                    </form>
                </div>
            @endif

            <!-- Followers -->
            <!-- Followers -->
            <x-estimates.follower-manager :estimate="$estimate" :potentialFollowers="$potentialFollowers" />

            <!-- Approval Chain -->
            @if($estimate->approval_chain_id && $estimate->approvalChain)
                <div class="bg-white shadow-sm ring-1 ring-slate-200 sm:rounded-xl px-4 py-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-slate-900">Approval Workflow</h3>
                        <a href="#" title="{{ $estimate->approvalChain->name }}"
                            class="text-xs text-indigo-600 hover:text-indigo-500">View Chain</a>
                    </div>

                    <div class="relative pl-3 border-l-2 border-slate-100 space-y-6 my-2">
                        @foreach($estimate->approvalChain->steps as $step)
                            @php $stepApproval = $estimate->approvals()->where('user_id', $step->user_id)->first(); @endphp
                            <div class="relative">
                                <!-- Status Dot -->
                                <div
                                    class="absolute -left-[19px] top-1 h-3 w-3 rounded-full border-2 border-white
                                                                                                                                            @if($stepApproval && $stepApproval->status === 'approved') bg-green-500
                                                                                                                                            @elseif($stepApproval && $stepApproval->status === 'rejected') bg-red-500
                                                                                                                                            @elseif($stepApproval && $stepApproval->status === 'pending') bg-yellow-400
                                                                                                                                            @else bg-slate-200
                                                                                                                                            @endif">
                                </div>

                                <div class="text-sm font-medium text-slate-900 leading-none">
                                    {{ ucfirst(str_replace('_', ' ', $step->role)) }}
                                </div>
                                <div class="text-xs text-slate-500 mt-1">{{ $step->user->name ?? 'Unassigned' }}</div>

                                @if($stepApproval && $stepApproval->status !== 'pending')
                                    <div class="mt-1 text-xs px-2 py-1 bg-slate-50 rounded inline-block">
                                        <span
                                            class="@if($stepApproval->status === 'approved') text-green-700 @else text-red-700 @endif font-medium">
                                            {{ ucfirst($stepApproval->status) }}
                                        </span>
                                        <span class="text-slate-400"> - {{ $stepApproval->updated_at->format('M d') }}</span>
                                    </div>
                                    @if($stepApproval->comments)
                                        <div class="mt-1 text-xs italic text-slate-500">"{{ $stepApproval->comments }}"</div>
                                    @endif
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Activity Log -->
            <div x-data="{ showActivityModal: false }"
                class="bg-white shadow-sm ring-1 ring-slate-200 sm:rounded-xl px-4 py-5 mt-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-slate-900">Activity Log</h3>
                    @if($activityLogs->count() > 5)
                        <button @click="showActivityModal = true"
                            class="text-xs font-semibold text-indigo-600 hover:text-indigo-500">
                            View All ({{ $activityLogs->count() }})
                        </button>
                    @endif
                </div>
                <div class="flow-root">
                    <ul role="list" class="-mb-8">
                        @forelse($activityLogs->take(5) as $log)
                            <li>
                                <div class="relative pb-8">
                                    @if(!$loop->last)
                                        <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-slate-200"
                                            aria-hidden="true"></span>
                                    @endif
                                    <div class="relative flex space-x-3">
                                        <div>
                                            <span
                                                class="h-8 w-8 rounded-full bg-slate-100 flex items-center justify-center ring-8 ring-white">
                                                <!-- Icon based on action -->
                                                @if(str_contains($log->action, 'created'))
                                                    <svg class="h-4 w-4 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd"
                                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                @elseif(str_contains($log->action, 'updated') || str_contains($log->action, 'edited'))
                                                    <svg class="h-4 w-4 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                                                        <path
                                                            d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                    </svg>
                                                @elseif(str_contains($log->action, 'deleted'))
                                                    <svg class="h-4 w-4 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd"
                                                            d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                @elseif(str_contains($log->action, 'approved'))
                                                    <svg class="h-4 w-4 text-green-600" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd"
                                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                @else
                                                    <svg class="h-4 w-4 text-slate-500" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd"
                                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                @endif
                                            </span>
                                        </div>
                                        <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                            <div>
                                                <p class="text-xs text-slate-500">{{ $log->description }} <span
                                                        class="font-medium text-slate-900">by
                                                        {{ $log->user->name ?? 'System' }}</span></p>
                                            </div>
                                            <div class="whitespace-nowrap text-right text-xs text-slate-500">
                                                <time
                                                    datetime="{{ $log->created_at }}">{{ $log->created_at->format('M d, H:i') }}</time>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="py-4 text-center text-xs text-slate-400">No activity recorded.</li>
                        @endforelse
                    </ul>

                    @if($activityLogs->count() > 5)
                        <div class="mt-4 text-center border-t border-slate-100 pt-3">
                            <button @click="showActivityModal = true"
                                class="text-xs font-semibold text-indigo-600 hover:text-indigo-500">
                                View Previous Activity
                            </button>
                        </div>
                    @endif
                </div>

                <!-- Activity Modal -->
                <x-estimates.activity-modal />
            </div>



            <!-- Changes vs Previous -->
            @if(isset($diff) && (!empty($diff['overview']) || !empty($diff['items']['added']) || !empty($diff['items']['removed']) || !empty($diff['items']['modified'])))
                <div x-data="{ showDiffModal: false }" class="mb-6">
                    <!-- Sidebar Summary Card -->
                    <div class="bg-indigo-50 shadow-sm ring-1 ring-indigo-200 sm:rounded-xl px-4 py-5">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-semibold text-indigo-900">Changes vs Previous</h3>
                            <button @click="showDiffModal = true" type="button"
                                class="text-xs font-semibold text-indigo-600 hover:text-indigo-500">View Details</button>
                        </div>
                        <p class="text-xs text-indigo-700 mb-3">
                            Comparison with v{{ $estimate->version - 1 }}.
                            @if(!empty($diff['overview'])) <span class="font-medium">{{ count($diff['overview']) }} fields
                            changed.</span> @endif
                            @if(!empty($diff['items']['added'])) <span
                            class="font-medium">{{ count($diff['items']['added']) }} items added.</span> @endif
                            @if(!empty($diff['items']['removed'])) <span
                            class="font-medium">{{ count($diff['items']['removed']) }} items removed.</span> @endif
                            @if(!empty($diff['items']['modified'])) <span
                            class="font-medium">{{ count($diff['items']['modified']) }} items modified.</span> @endif
                        </p>
                        <button @click="showDiffModal = true" type="button"
                            class="w-full rounded bg-indigo-600/10 px-2 py-1.5 text-xs font-semibold text-indigo-700 shadow-sm ring-1 ring-inset ring-indigo-200 hover:bg-indigo-600/20">
                            Show All Changes
                        </button>
                    </div>

                    <!-- Diff Modal -->
                    <x-estimates.diff-modal />
            @endif

                <!-- History / Versions -->
                @if($allVersions->count() > 1)
                    <div class="bg-white shadow-sm ring-1 ring-slate-200 sm:rounded-xl px-4 py-5">
                        <h3 class="text-sm font-semibold text-slate-900 mb-3">Version History</h3>
                        <div class="space-y-2">
                            @foreach($allVersions as $ver)
                                <a href="{{ route('estimates.show', $ver) }}"
                                    class="flex items-center justify-between p-2 rounded-md hover:bg-slate-50 {{ $ver->id === $estimate->id ? 'bg-indigo-50 ring-1 ring-indigo-200' : '' }}">
                                    <div class="text-sm">
                                        <span class="font-medium text-slate-900">v{{ $ver->version }}</span>
                                        <span
                                            class="text-slate-500 text-xs ml-2">{{ $ver->estimate_date->format('M d') }}</span>
                                    </div>
                                    @if($ver->is_current_version) <span
                                        class="text-[10px] uppercase font-bold text-green-600 bg-green-50 px-1.5 py-0.5 rounded">Current</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Signature -->
                @if($estimate->signed_at)
                    <div class="bg-white shadow-sm ring-1 ring-slate-200 sm:rounded-xl px-4 py-5">
                        <h3 class="text-sm font-semibold text-slate-900 mb-3">Signed by Client</h3>
                        <div class="p-4 bg-slate-50 border border-slate-100 rounded-lg">
                            <img src="{{ $estimate->signature }}" alt="Signature" class="h-12 mb-2 mix-blend-multiply">
                            <div class="text-xs text-slate-500">
                                <div>Signed: {{ $estimate->signed_at->format('M d, Y h:i A') }}</div>
                                <div>IP: {{ $estimate->signer_ip }}</div>
                            </div>
                        </div>
                    </div>
                @endif

            </div>
        </div>

        <!-- Item Comments Modal -->
        <x-estimates.item-comments-modal :estimate="$estimate" />
        @push('scripts')
            <script>
                // Defensive initialization to prevent ReferenceErrors
                window.estimate = @json($estimate);
                window.totals = {
                    subtotal: {{ $estimate->subtotal ?? 0 }},
                    totalTax: {{ $estimate->total_tax ?? 0 }},
                    discount: {{ $estimate->discount_total ?? 0 }},
                    grandTotal: {{ $estimate->grand_total ?? 0 }} 
                                                            };

                const defaults = {
                    hasCustomItems: () => false,
                    isSubmitting: false,
                    couponValid: false,
                    couponMessage: '',
                    couponInput: '',
                    appliedCouponCode: '',
                    productPicker: { isOpen: false, search: '', sectionIndex: null },
                    internalNoteModal: { isOpen: false, activeItem: null },
                    configModal: { isOpen: false, product: null, options: {}, basePrice: 0 }
                };

                for (const [key, value] of Object.entries(defaults)) {
                    if (typeof window[key] === 'undefined') {
                        window[key] = value;
                    }
                }



                window.toggleCommentStatus = async function (commentId, currentStatus) {
                    const newStatus = currentStatus === 'pending' ? 'clarified' : 'pending';
                    try {
                        const response = await fetch(`/comments/${commentId}/status`, {
                            method: 'PATCH',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: JSON.stringify({ status: newStatus })
                        });
                        if (response.ok) { window.location.reload(); }
                    } catch (e) { console.error(e); }
                };
            </script>
        @endpush
</x-app-layout>
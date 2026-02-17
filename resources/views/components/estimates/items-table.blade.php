@props(['estimate', 'items', 'isPackage' => false, 'showRoomTotal' => false, 'sectionTotal' => 0])

<div class="overflow-x-auto">
    <table class="min-w-[800px] w-full divide-y divide-slate-200">
        <thead class="bg-slate-50/50">
            <tr>
                @if(!$isPackage)
                    <th class="px-3 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest w-16">
                        Image
                    </th>
                    <th class="px-3 py-4 text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest w-40">
                        Unit Configuration
                    </th>
                @endif
                <th class="px-3 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                    Item Details
                </th>
                @if(!$isPackage)
                    <th class="px-3 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest w-28">
                        Size
                    </th>
                @endif
                <th class="px-3 py-4 text-right text-[10px] font-bold text-slate-400 uppercase tracking-widest w-28">
                    Price
                </th>
                @if(!$isPackage)
                    <th class="px-3 py-4 text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest w-32">
                        Quantity
                    </th>
                @endif
                <th class="px-3 py-4 text-right text-[10px] font-bold text-slate-400 uppercase tracking-widest w-32">
                    Total
                </th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 bg-white">
            @forelse($items as $item)
                <tr class="group hover:bg-slate-50/30 transition-colors">
                    @php
                        $allComments = $item->comments->flatMap(function ($c) {
                            return collect([$c])->merge($c->replies);
                        })->unique('id')->sortBy('created_at')->values()->map(function ($c) {
                            $c->formatted_date = $c->created_at->format('M j, g:i A');
                            return $c;
                        });
                    @endphp
                    @if(!$isPackage)
                        <td class="px-3 py-4 align-middle">
                            @if($item->product && $item->product->primary_image_url)
                                <div class="relative h-12 w-12 mx-auto">
                                    <img src="{{ $item->product->primary_image_url }}"
                                        class="h-full w-full object-cover rounded-lg shadow-sm ring-1 ring-slate-200">
                                </div>
                            @else
                                <div class="h-12 w-12 bg-slate-50 rounded-lg mx-auto flex items-center justify-center ring-1 ring-slate-200 border border-dashed border-slate-300">
                                    <svg class="h-6 w-6 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            @endif
                        </td>
                        <td class="px-3 py-4 text-sm text-center align-middle border-b border-slate-100 last:border-0">
                            <div class="font-bold text-slate-900">
                                @if($item->unitType) {{ $item->unitType->name }} @endif
                            </div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">
                                {{ $item->unit_type }}
                            </div>
                        </td>
                    @endif
                    <td class="px-3 py-4 text-sm text-slate-900 border-b border-slate-100 last:border-0">
                        <div class="min-w-0">
                            <button @click="openItemComments({{ $item->id }}, {{ Js::from($item->name) }}, {{ Js::from($allComments) }})" 
                                    class="font-bold text-slate-900 mb-0.5 hover:text-indigo-600 transition-colors text-left w-full">
                                {{ $item->name }}
                            </button>
                            @if($item->description)
                                <div class="text-xs text-slate-500 leading-relaxed max-w-sm mb-1.5">
                                    {{ $item->description }}
                                </div>
                            @endif
                            @if(!empty($item->options) && is_array($item->options))
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach($item->options as $option)
                                        <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600 ring-1 ring-inset ring-slate-200">
                                            {{ $option['name'] }}: {{ $option['value'] }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                            @if($item->internal_note)
                                <div class="mt-2 text-[10px] text-amber-700 bg-amber-50 px-2 py-1 rounded-lg border border-amber-200 inline-flex items-center gap-1.5 font-medium shadow-xs">
                                    <svg class="h-3 w-3 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    <span class="opacity-75 uppercase tracking-wider text-[9px] font-bold">Internal Note:</span> {{ $item->internal_note }}
                                </div>
                            @endif
                            
                            <!-- Comment Button -->
                            <button @click="openItemComments({{ $item->id }}, {{ Js::from($item->name) }}, {{ Js::from($allComments) }})" 
                                type="button"
                                class="mt-2 inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-[10px] font-medium transition-colors {{ $allComments->isNotEmpty() ? 'bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-700/10' : 'text-slate-500 hover:bg-slate-100' }}">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                </svg>
                                @if($allComments->isNotEmpty())
                                    {{ $allComments->count() }} {{ Str::plural('Comment', $allComments->count()) }}
                                @else
                                    Comment
                                @endif
                            </button>
                        </div>
                    </td>
                    @if(!$isPackage)
                        <td class="px-3 py-4 text-sm text-slate-900 border-b border-slate-100 last:border-0">
                            @if($item->length || $item->width || $item->height)
                                <div class="flex flex-col gap-1.5">
                                    @foreach(['L' => 'length', 'W' => 'width', 'H' => 'height'] as $label => $attr)
                                        @if($item->$attr)
                                            <div class="flex items-center gap-2">
                                                <span class="text-[10px] font-bold text-slate-600 uppercase w-3">{{ $label }}</span>
                                                <span class="text-xs font-medium text-slate-900">{{ $item->$attr + 0 }} ft</span>
                                            </div>
                                        @endif
                                    @endforeach
                                    @if($item->size > 0)
                                        <div class="mt-2 pt-2 border-t border-slate-100">
                                            <div class="text-[10px] font-bold text-indigo-600 uppercase tracking-wider mb-0.5">
                                                {{ $item->formula_label }}
                                            </div>
                                            <div class="text-xs font-bold text-slate-900">
                                                {{ number_format($item->size, 2) }} <span class="text-slate-500 font-medium">{{ $item->unit_type }}</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <span class="text-xs text-slate-400">-</span>
                            @endif
                        </td>
                    @endif
                    <td class="px-3 py-4 text-sm text-right text-slate-600 font-medium align-middle border-b border-slate-100 last:border-0">
                        {{ $estimate->currency_symbol }} {{ number_format($item->unit_price, 2) }}
                    </td>
                    @if(!$isPackage)
                        <td class="px-3 py-4 text-sm text-center align-middle border-b border-slate-100 last:border-0">
                            <div class="font-bold text-slate-900">{{ $item->quantity }}</div>
                        </td>
                    @endif
                    <td class="px-3 py-4 text-sm text-right font-bold text-slate-900 align-middle border-b border-slate-100 last:border-0">
                        {{ $estimate->currency_symbol }} {{ number_format($item->total, 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $isPackage ? '3' : '6' }}" class="px-3 py-6 text-center text-sm text-slate-500 italic">
                        No items found.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if($showRoomTotal)
            <tfoot class="bg-slate-50">
                <tr>
                    <td colspan="{{ $isPackage ? '1' : '3' }}" class="px-3 py-2 text-xs font-medium text-slate-500 text-right">
                        Room Total
                    </td>
                    <td class="px-3 py-2 text-xs font-bold text-slate-900 text-right">
                        {{ number_format($sectionTotal, 2) }}
                    </td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>

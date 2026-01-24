<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <!-- Header -->
        <div class="mb-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div class="space-y-1">
                <nav class="flex mb-2" aria-label="Breadcrumb">
                    <ol role="list" class="flex items-center space-x-2">
                        <li>
                            <a href="{{ route('templates.index') }}" class="text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-indigo-600 transition-colors">Templates</a>
                        </li>
                        <li class="flex items-center space-x-2">
                            <svg class="h-3 w-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path d="M9 5l7 7-7 7"/></svg>
                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-900">View Template</span>
                        </li>
                    </ol>
                </nav>
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ $template->name }}</h1>
                <p class="text-slate-500 font-medium">Detailed configuration for this room template.</p>
            </div>
            
            <div class="flex items-center gap-3">
                <a href="{{ route('templates.edit', $template) }}"
                    class="inline-flex items-center px-6 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-bold shadow-sm hover:bg-slate-800 transition-all active:scale-95 group">
                    <svg class="mr-2 h-4 w-4 text-indigo-400 group-hover:rotate-6 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    Edit Template
                </a>
                <a href="{{ route('templates.index') }}"
                    class="inline-flex items-center px-6 py-2.5 rounded-xl bg-white text-slate-600 text-sm font-bold shadow-sm border border-slate-200 hover:bg-slate-50 transition-all active:scale-95">
                    Back to List
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10 items-start">
            <!-- Details & Stats -->
            <div class="lg:col-span-1 space-y-8">
                <!-- Summary Card -->
                <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-100">
                    <h2 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-6">Overview</h2>
                    
                    <div class="space-y-6">
                        <div>
                            <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Description</div>
                            <p class="text-slate-700 font-medium leading-relaxed">
                                {{ $template->description ?: 'No additional notes provided for this template.' }}
                            </p>
                        </div>

                        <div class="pt-6 border-t border-slate-50 grid grid-cols-2 gap-4">
                            <div>
                                <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Total Items</div>
                                <div class="text-2xl font-black text-slate-900">{{ count($template->items ?? []) }}</div>
                            </div>
                            <div>
                                <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Created</div>
                                <div class="text-sm font-bold text-slate-900">{{ $template->created_at->format('M d, Y') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Danger Zone -->
                <div class="bg-rose-50/50 rounded-3xl p-8 border border-rose-100">
                    <h2 class="text-xs font-black text-rose-600 uppercase tracking-widest mb-4">Management</h2>
                    <p class="text-[11px] text-rose-400 font-bold uppercase tracking-tight mb-6 leading-normal">Deleting this template will not affect existing estimates but it will be permanently removed from the library.</p>
                    
                    <form action="{{ route('templates.destroy', $template) }}" method="POST"
                        onsubmit="return confirm('Permanently delete this room template?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="w-full py-3 rounded-2xl bg-white border border-rose-200 text-rose-600 text-xs font-black uppercase tracking-widest hover:bg-rose-600 hover:text-white transition-all shadow-sm">
                            Delete Template
                        </button>
                    </form>
                </div>
            </div>

            <!-- Items Table -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="px-8 py-8 border-b border-slate-50 bg-slate-50/20">
                        <h3 class="text-xl font-black text-slate-900 tracking-tight">Included Products</h3>
                        <p class="mt-1 text-sm text-slate-400 font-medium tracking-tight">Products that will be pre-filled when using this room template.</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100">
                            <thead>
                                <tr class="bg-slate-50/50">
                                    <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Product</th>
                                    <th class="px-6 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">Qty</th>
                                    <th class="px-6 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">Dimensions</th>
                                    <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Unit Type</th>
                                    <th class="px-8 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Est. Price</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 bg-white">
                                @forelse($template->items as $item)
                                    <tr class="hover:bg-slate-50/30 transition-colors border-b border-slate-50 last:border-0">
                                        <td class="px-8 py-6">
                                            <div class="flex flex-col">
                                                <div class="text-sm font-black text-slate-900 leading-tight group-hover:text-slate-600">{{ $item['item_name'] }}</div>
                                                @if(isset($item['description']) && $item['description'])
                                                    <div class="text-[11px] font-bold text-slate-400 mt-0.5">{{ $item['description'] }}</div>
                                                @endif
                                                
                                                @if(isset($item['options']) && is_array($item['options']))
                                                    <div class="flex flex-wrap gap-1 mt-2">
                                                        @foreach($item['options'] as $opt)
                                                            <span class="inline-flex px-2 py-0.5 rounded-md bg-indigo-50 text-[9px] font-black text-indigo-600 border border-indigo-100 uppercase tracking-tighter">
                                                                {{ $opt['name'] }}: {{ $opt['value'] }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-6 text-center">
                                            <span class="text-sm font-black text-slate-900">{{ $item['quantity'] }}</span>
                                        </td>
                                        <td class="px-6 py-6 text-center">
                                            @if(!empty($item['length']) || !empty($item['width']) || !empty($item['height']))
                                                <span class="text-[11px] font-bold text-slate-500">
                                                    {{ $item['length'] ?? '-' }} × {{ $item['width'] ?? '-' }} × {{ $item['height'] ?? '-' }}
                                                </span>
                                            @else
                                                <span class="text-[11px] text-slate-300">-</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-6 font-bold">
                                            <span class="inline-flex items-center px-2 py-1 rounded-lg text-[10px] font-black bg-slate-100 text-slate-500 uppercase tracking-widest border border-slate-200/50">
                                                {{ $item['unit_type'] ?? 'nos' }}
                                            </span>
                                        </td>
                                        <td class="px-8 py-6 text-right">
                                            <div class="text-sm font-black text-slate-900">₹{{ number_format($item['unit_price'] ?? 0, 2) }}</div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-8 py-12 text-center text-slate-400 font-medium">No items defined in this template.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
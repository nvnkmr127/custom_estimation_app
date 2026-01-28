<div x-data="{ 
    showUploadModal: @entangle('showUploadModal'), 
    showRetireModal: @entangle('showRetireModal'), 
    showSuggestModal: @entangle('showSuggestModal'),
    showEditModal: @entangle('showEditModal'),
    initQuill() {
        if (!document.getElementById('quill-editor')) return;
        if (this.quill) return;
        this.quill = new Quill('#quill-editor', { theme: 'snow' });
        
        let initialValue = this.$wire.get('productForm.description');
        this.quill.root.innerHTML = (typeof initialValue === 'string') ? initialValue : '';
        
        this.quill.on('text-change', () => {
             this.$wire.set('productForm.description', this.quill.root.innerHTML);
        });
        
        window.addEventListener('init-quill', () => {
            setTimeout(() => this.initQuill(), 100);
        });
        
        // Watch for external changes to description (e.g. when opening a new product)
        this.$watch('showEditModal', value => {
            if (value && this.quill) {
                let val = this.$wire.get('productForm.description');
                this.quill.root.innerHTML = (typeof val === 'string') ? val : '';
            }
        });
    }
}" class="min-h-screen pb-12">
    <!-- Header Section -->
    <div class="mb-10">
        <nav class="flex mb-4" aria-label="Breadcrumb">
            <ol role="list" class="flex items-center space-x-2">
                <li>
                    <div>
                        <a href="{{ route('dashboard') }}" class="text-slate-400 hover:text-slate-500">
                            <svg class="h-5 w-5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M9.293 2.293a1 1 0 011.414 0l7 7A1 1 0 0117 11h-1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-3a1 1 0 00-1-1H9a1 1 0 00-1 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-6H3a1 1 0 01-.707-1.707l7-7z" clip-rule="evenodd" />
                            </svg>
                            <span class="sr-only">Dashboard</span>
                        </a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="h-5 w-5 flex-shrink-0 text-slate-300" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path d="M5.555 17.776l8-16 .894.448-8 16-.894-.448z" />
                        </svg>
                        <span class="ml-2 text-sm font-medium text-slate-700" aria-current="page">Product Library</span>
                    </div>
                </li>
            </ol>
        </nav>
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Product Library</h1>
                <p class="mt-2 text-slate-500 font-medium">Manage and organize your product catalog with ease.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                @can('create', App\Models\Product::class)
                    <button @click="showUploadModal = true" type="button"
                        class="inline-flex items-center px-4 py-2.5 rounded-xl bg-white text-slate-700 text-sm font-bold shadow-sm border border-slate-200 hover:bg-slate-50 hover:border-slate-300 transition-all active:scale-95">
                        <svg class="mr-2 h-5 w-5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                        </svg>
                        Bulk Import
                    </button>
                    <button wire:click="editProduct()"
                        class="inline-flex items-center px-4 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-bold shadow-md shadow-indigo-200 hover:bg-indigo-700 transition-all active:scale-95">
                        <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Product
                    </button>
                @else
                    <button @click="showSuggestModal = true" type="button"
                        class="inline-flex items-center px-4 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-bold shadow-md shadow-indigo-200 hover:bg-indigo-700 transition-all active:scale-95">
                        <svg class="mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke_width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.854 1.5-2.261M12 9c.225 0 .445.012.663.033A8.956 8.956 0 015.658 9M10 18v-5.25m0 0a6.01 6.01 0 01-1.5-.189m1.5.189a6.01 6.01 0 001.5-.189m-3.75 2.147a14.406 14.406 0 00-3 0m3 2.383a14.406 14.406 0 00-3 0M9.75 18v-.192c0-.983-.658-1.854-1.5-2.261" />
                        </svg>
                        Suggest Product
                    </button>
                @endcan
                @if(auth()->user()->hasPermission('approve_products') ?? true)
                    <a href="{{ route('products.pending') }}"
                        class="inline-flex items-center px-4 py-2.5 rounded-xl bg-white text-slate-700 text-sm font-bold shadow-sm border border-slate-200 hover:bg-slate-50 transition-all active:scale-95">
                        Pending
                        @if($pendingCount > 0)
                            <span class="ml-2 px-2 py-0.5 rounded-full bg-rose-100 text-rose-600 text-[10px] uppercase font-black">
                                {{ $pendingCount }}
                            </span>
                        @endif
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="bg-white/60 backdrop-blur-md sticky top-4 z-30 p-4 rounded-2xl border border-slate-200 shadow-sm mb-8">
        <div class="flex flex-col lg:flex-row gap-4 items-center">
            <!-- Search Input -->
            <div class="relative flex-1 w-full">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <input type="text" wire:model.live.debounce.300ms="search"
                    class="block w-full rounded-xl border-slate-200 bg-slate-50/50 py-3 pl-11 pr-4 text-slate-900 placeholder:text-slate-400 focus:ring-2 focus:ring-indigo-600 focus:border-transparent transition-all sm:text-sm"
                    placeholder="Search by name, SKU, or details...">
                <div wire:loading wire:target="search" class="absolute inset-y-0 right-0 pr-4 flex items-center">
                    <svg class="animate-spin h-4 w-4 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke_width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                <!-- Category Dropdown -->
                <select wire:model.live="category"
                    class="flex-1 lg:flex-none min-w-[160px] rounded-xl border-slate-200 py-3 pl-4 pr-10 text-slate-700 text-sm focus:ring-2 focus:ring-indigo-600 shadow-sm transition-all appearance-none bg-no-repeat bg-[right_1rem_center] bg-[length:1em]">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>

                <!-- Status Dropdown -->
                <select wire:model.live="status"
                    class="flex-1 lg:flex-none min-w-[140px] rounded-xl border-slate-200 py-3 pl-4 pr-10 text-slate-700 text-sm focus:ring-2 focus:ring-indigo-600 shadow-sm transition-all appearance-none bg-no-repeat bg-[right_1rem_center] bg-[length:1em]">
                    <option value="">Any Status</option>
                    <option value="active">Active</option>
                    <option value="retired">Retired</option>
                    <option value="pending">Pending</option>
                </select>

                <!-- View Mode Toggle -->
                <div class="flex items-center p-1 bg-slate-100 rounded-xl border border-slate-200">
                    <button wire:click="setViewMode('table')" 
                        class="p-2 rounded-lg transition-all {{ $viewMode === 'table' ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-500 hover:text-slate-700' }}">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <button wire:click="setViewMode('grid')"
                        class="p-2 rounded-lg transition-all {{ $viewMode === 'grid' ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-500 hover:text-slate-700' }}">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                    </button>
                </div>

                @if($search || $category || $status)
                    <button wire:click="clearFilters" class="text-xs font-black text-rose-500 hover:text-rose-700 uppercase tracking-widest px-2">
                        Reset
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Product Listing -->
    <div class="relative">
        <div wire:loading.delay.longest class="absolute inset-x-0 -top-1 z-50">
            <div class="h-1 w-full bg-indigo-100 overflow-hidden rounded-full">
                <div class="h-full bg-indigo-600 animate-progress origin-left-right"></div>
            </div>
        </div>

        @if($products->count() > 0)
            @if($viewMode === 'table')
                <!-- Table View Card -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100">
                            <thead>
                                <tr class="bg-slate-50/50">
                                    <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Product</th>
                                    <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Category</th>
                                    <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Pricing</th>
                                    <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                                    <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 bg-white">
                                @foreach($products as $product)
                                    <tr wire:key="row-{{ $product->id }}" class="hover:bg-slate-50/50 transition-all group">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-4">
                                                <div class="h-12 w-12 rounded-xl overflow-hidden bg-slate-100 border border-slate-200 shrink-0 group-hover:scale-105 transition-transform">
                                                    @if($product->primary_image_url)
                                                        <img src="{{ $product->primary_image_url }}" alt="" class="h-full w-full object-cover">
                                                    @else
                                                        <div class="h-full w-full flex items-center justify-center text-slate-300">
                                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke_width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div>
                                                    <button type="button" wire:click="editProduct({{ $product->id }})" class="text-sm font-bold text-slate-900 leading-tight hover:text-indigo-600 transition-colors text-left">
                                                        {{ $product->name }}
                                                    </button>
                                                    @if($product->sku)
                                                        <div class="text-[11px] font-bold text-slate-400 uppercase mt-1">SKU: {{ $product->sku }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-bold bg-slate-100 text-slate-600 border border-slate-200/50">
                                                {{ $product->category->name ?? 'Uncategorized' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div>
                                                <span class="text-sm font-black text-slate-900">₹{{ number_format($product->unit_price, 2) }}</span>
                                                <span class="text-[10px] text-slate-400 font-bold uppercase ml-1">/ {{ $product->unit_type }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @php
                                                $statusClasses = [
                                                    'active' => 'bg-emerald-50 text-emerald-600 border-emerald-100',
                                                    'retired' => 'bg-slate-50 text-slate-500 border-slate-200',
                                                    'pending' => 'bg-amber-50 text-amber-600 border-amber-100',
                                                ][$product->status] ?? 'bg-slate-50 text-slate-500';
                                            @endphp
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border {{ $statusClasses }}">
                                                {{ $product->status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right">
                                            <div class="flex items-center justify-end gap-1">
                                                <button wire:click="editProduct({{ $product->id }})" 
                                                    class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all">
                                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke_width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                                </button>
                                                <div class="relative" x-data="{ open: false }">
                                                    <button @click="open = !open" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-all">
                                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke_width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" /></svg>
                                                    </button>
                                                    <div x-show="open" @click.away="open = false" 
                                                        class="absolute right-0 mt-2 w-48 rounded-xl bg-white border border-slate-200 shadow-xl z-50 overflow-hidden" 
                                                        style="display: none;">
                                                        @if($product->status === 'active')
                                                            <button @click="$wire.set('activeProductId', {{ $product->id }}); showRetireModal = true; open = false" 
                                                                class="flex w-full items-center px-4 py-3 text-sm text-rose-600 font-bold hover:bg-rose-50 transition-colors">
                                                                <svg class="mr-3 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke_width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                                                Retire Product
                                                            </button>
                                                        @elseif($product->status === 'retired')
                                                            <button wire:click="activateProduct({{ $product->id }}); open = false" 
                                                                class="flex w-full items-center px-4 py-3 text-sm text-emerald-600 font-bold hover:bg-emerald-50 transition-colors">
                                                                <svg class="mr-3 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke_width="2" d="M5 13l4 4L19 7" /></svg>
                                                                Reactivate
                                                            </button>
                                                        @endif
                                                        <a href="{{ route('products.edit', $product) }}" class="flex w-full items-center px-4 py-3 text-sm text-slate-700 font-bold hover:bg-slate-50 transition-colors border-t border-slate-50">
                                                            <svg class="mr-3 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke_width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                                            Full Edit View
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <!-- Grid View -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @foreach($products as $product)
                        <div wire:key="card-{{ $product->id }}" class="group bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                            <!-- Card Image -->
                            <div class="aspect-[4/3] bg-slate-100 relative overflow-hidden">
                                @if($product->primary_image_url)
                                    <img src="{{ $product->primary_image_url }}" alt="" class="h-full w-full object-cover group-hover:scale-110 transition-transform duration-500">
                                @else
                                    <div class="h-full w-full flex items-center justify-center text-slate-300">
                                        <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke_width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    </div>
                                @endif
                                <div class="absolute top-3 right-3 flex flex-col gap-2">
                                    <button wire:click="editProduct({{ $product->id }})" 
                                        class="p-2 rounded-xl bg-white/90 backdrop-blur-sm text-slate-600 shadow-sm opacity-0 group-hover:opacity-100 transition-all hover:text-indigo-600 active:scale-95">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke_width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    </button>
                                </div>
                                <div class="absolute bottom-3 left-3">
                                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest backdrop-blur-md border {{ 
                                        $product->status === 'active' ? 'bg-emerald-500/80 text-white border-emerald-400' : 
                                        ($product->status === 'retired' ? 'bg-slate-500/80 text-white border-slate-400' : 'bg-amber-500/80 text-white border-amber-400')
                                    }}">
                                        {{ $product->status }}
                                    </span>
                                </div>
                            </div>
                            <!-- Card Content -->
                            <div class="p-5">
                                <div class="mb-1">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ $product->category->name ?? 'Uncategorized' }}</span>
                                </div>
                                <button type="button" wire:click="editProduct({{ $product->id }})" class="block w-full text-left">
                                    <h3 class="text-base font-black text-slate-900 leading-tight group-hover:text-indigo-600 transition-colors">{{ $product->name }}</h3>
                                </button>
                                @if($product->sku)
                                    <p class="text-[11px] font-bold text-slate-400 mt-1">SKU: {{ $product->sku }}</p>
                                @endif
                                <div class="mt-4 flex items-center justify-between border-t border-slate-50 pt-4">
                                    <div>
                                        <span class="text-lg font-black text-slate-900">₹{{ number_format($product->unit_price, 2) }}</span>
                                        <span class="text-[10px] text-slate-400 font-bold uppercase ml-1">/ {{ $product->unit_type }}</span>
                                    </div>
                                    <button wire:click="editProduct({{ $product->id }})" class="text-xs font-black text-indigo-600 hover:text-indigo-800 transition-colors">Details &rarr;</button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Pagination Section -->
            <div class="mt-10 px-6 py-4 bg-white/50 backdrop-blur-sm rounded-2xl border border-slate-200">
                {{ $products->links() }}
            </div>
        @else
            <!-- Empty State -->
            <div class="px-6 py-20 text-center bg-white rounded-3xl border border-dotted border-slate-300 shadow-inner">
                <div class="inline-flex h-20 w-20 items-center justify-center rounded-full bg-slate-50 text-slate-300 mb-6 border border-slate-100">
                    <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke_width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                </div>
                <h3 class="text-xl font-black text-slate-900">No products found</h3>
                <p class="mt-2 text-slate-500 max-w-sm mx-auto font-medium">Try adjusting your filters or search terms to find what you're looking for.</p>
                @if($search || $category || $status)
                    <button wire:click="clearFilters" class="mt-6 px-6 py-2.5 rounded-xl bg-slate-900 text-white text-sm font-bold hover:bg-slate-800 transition-all shadow-lg active:scale-95">
                        Clear all filters
                    </button>
                @endif
            </div>
        @endif
    </div>

    <!-- Modals Section -->
    
    <!-- Edit / Create Product Modal -->
    <div x-show="showEditModal" class="relative z-[60]" style="display: none;" x-init="$watch('showEditModal', value => { if(value) setTimeout(() => initQuill(), 100) })">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity z-[55]"></div>
        
        <div class="fixed inset-0 z-[60] overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div x-show="showEditModal" 
                    x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all w-full max-w-5xl border border-slate-100"
                    @click.away="showEditModal = false">
                    
                    <div class="px-8 pt-8 pb-4 flex items-center justify-between border-b border-slate-50">
                        <div>
                            <h3 class="text-2xl font-black text-slate-900 tracking-tight">
                                {{ $editingProduct ? ($isEditing ? 'Edit Product' : 'Product Details') : 'Add New Product' }}
                            </h3>
                            <p class="text-sm text-slate-500 font-medium tracking-tight">
                                {{ $isEditing ? 'Update product details, pricing, and variants.' : 'View complete product information.' }}
                            </p>
                        </div>
                        <button @click="showEditModal = false" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition-all">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke_width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="px-8 py-8 overflow-y-auto max-h-[75vh]">
                        <!-- Global Error Messaging -->
                        @if (session()->has('error'))
                            <div class="mb-8 p-4 rounded-2xl bg-rose-50 border border-rose-100 flex items-start gap-3 animate-shake">
                                <div class="h-10 w-10 rounded-xl bg-white shadow-sm flex items-center justify-center text-rose-500 shrink-0">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke_width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-black text-rose-900 leading-tight">Something went wrong</h4>
                                    <p class="text-xs text-rose-600 font-medium mt-1">{{ session('error') }}</p>
                                </div>
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="mb-8 p-4 rounded-2xl bg-rose-50 border border-rose-100 flex items-start gap-3">
                                <div class="h-10 w-10 rounded-xl bg-white shadow-sm flex items-center justify-center text-rose-500 shrink-0">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke_width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-black text-rose-900 leading-tight">There are errors in your form</h4>
                                    <ul class="mt-2 space-y-1">
                                        @foreach ($errors->all() as $error)
                                            <li class="text-xs text-rose-600 font-medium list-disc list-inside">{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                            <!-- Left Column: Core Info -->
                            <div class="space-y-10">
                                <div>
                                    <h4 class="text-xs font-black text-indigo-600 uppercase tracking-widest mb-6">Product Information</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="col-span-full">
                                            <label class="block text-[11px] font-black uppercase tracking-widest text-slate-400 mb-2">Product Name <span class="text-rose-500">*</span></label>
                                            <input type="text" wire:model="productForm.name" :disabled="!$wire.isEditing" class="block w-full rounded-2xl border-slate-200 py-3 shadow-sm focus:ring-2 focus:ring-indigo-600 focus:border-transparent transition-all sm:text-sm disabled:bg-slate-50 disabled:text-slate-500">
                                            @error('productForm.name') <span class="text-[11px] text-rose-500 font-bold mt-1 block">{{ $message }}</span> @enderror
                                        </div>

                                        <div>
                                            <label class="block text-[11px] font-black uppercase tracking-widest text-slate-400 mb-2">SKU Code</label>
                                            <input type="text" wire:model="productForm.sku" :disabled="!$wire.isEditing" placeholder="e.g. PRD-001" class="block w-full rounded-2xl border-slate-200 py-3 shadow-sm focus:ring-2 focus:ring-indigo-600 transition-all sm:text-sm disabled:bg-slate-50 disabled:text-slate-500">
                                            @error('productForm.sku') <span class="text-[11px] text-rose-500 font-bold mt-1 block">{{ $message }}</span> @enderror
                                        </div>

                                        <div>
                                            <label class="block text-[11px] font-black uppercase tracking-widest text-slate-400 mb-2">Category <span class="text-rose-500">*</span></label>
                                            <select wire:model="productForm.category_id" :disabled="!$wire.isEditing" class="block w-full rounded-2xl border-slate-200 py-3 shadow-sm focus:ring-2 focus:ring-indigo-600 transition-all sm:text-sm appearance-none bg-no-repeat bg-[right_1rem_center] bg-[length:1em] disabled:bg-slate-50 disabled:text-slate-500">
                                                <option value="">Select Category</option>
                                                @foreach($categories as $cat)
                                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-span-full">
                                            <label class="block text-[11px] font-black uppercase tracking-widest text-slate-400 mb-2">Tags (Comma separated)</label>
                                            <input type="text" wire:model="productForm.tags" :disabled="!$wire.isEditing" placeholder="premium, wood, custom" class="block w-full rounded-2xl border-slate-200 py-3 shadow-sm focus:ring-2 focus:ring-indigo-600 transition-all sm:text-sm disabled:bg-slate-50 disabled:text-slate-500">
                                        </div>

                                        <div class="col-span-full">
                                            <label class="block text-[11px] font-black uppercase tracking-widest text-slate-400 mb-2">Description</label>
                                            <div wire:ignore>
                                                <div id="quill-editor" :class="{'bg-slate-50': !$wire.isEditing}" class="min-h-[150px] bg-white rounded-2xl border-slate-200 shadow-sm"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <h4 class="text-xs font-black text-indigo-600 uppercase tracking-widest mb-6">Pricing & Dimensions</h4>
                                    <div class="space-y-6">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <label class="block text-[11px] font-black uppercase tracking-widest text-slate-400 mb-2">Base Price</label>
                                                <div class="relative">
                                                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 font-bold text-sm">₹</span>
                                                    <input type="number" step="0.01" wire:model="productForm.unit_price" :disabled="!$wire.isEditing" class="block w-full rounded-2xl border-slate-200 py-3 pl-8 shadow-sm focus:ring-2 focus:ring-indigo-600 transition-all sm:text-sm disabled:bg-slate-50 disabled:text-slate-500">
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-black uppercase tracking-widest text-slate-400 mb-2">Tax 1 (%)</label>
                                                <input type="number" step="0.01" wire:model="productForm.tax_1" :disabled="!$wire.isEditing" class="block w-full rounded-2xl border-slate-200 py-3 shadow-sm focus:ring-2 focus:ring-indigo-600 transition-all sm:text-sm disabled:bg-slate-50 disabled:text-slate-500">
                                            </div>
                                        </div>
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <label class="block text-[11px] font-black uppercase tracking-widest text-slate-400 mb-2">Unit Classification</label>
                                                <select wire:model.live="productForm.unit_type_id" :disabled="!$wire.isEditing" class="block w-full rounded-2xl border-slate-200 py-3 shadow-sm focus:ring-2 focus:ring-indigo-600 transition-all sm:text-sm appearance-none bg-no-repeat bg-[right_1rem_center] bg-[length:1em] disabled:bg-slate-50 disabled:text-slate-500">
                                                    <option value="">Manual Entry</option>
                                                    @foreach($unitTypes as $ut)
                                                        <option value="{{ $ut->id }}">{{ $ut->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-black uppercase tracking-widest text-slate-400 mb-2">Primary Unit</label>
                                                @if($productForm['unit_type_id'])
                                                    <select wire:model="productForm.unit_type" :disabled="!$wire.isEditing" class="block w-full rounded-2xl border-slate-200 py-3 shadow-sm focus:ring-2 focus:ring-indigo-600 transition-all sm:text-sm appearance-none bg-no-repeat bg-[right_1rem_center] bg-[length:1em] disabled:bg-slate-50 disabled:text-slate-500">
                                                        @php
                                                            $currentType = $unitTypes->find($productForm['unit_type_id']);
                                                        @endphp
                                                        @if($currentType)
                                                            @foreach($currentType->units as $u)
                                                                <option value="{{ $u }}">{{ $u }}</option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                @else
                                                    <input type="text" wire:model="productForm.unit_type" :disabled="!$wire.isEditing" placeholder="e.g. nos, boxes" class="block w-full rounded-2xl border-slate-200 py-3 shadow-sm focus:ring-2 focus:ring-indigo-600 transition-all sm:text-sm disabled:bg-slate-50 disabled:text-slate-500">
                                                @endif
                                            </div>
                                        </div>

                                        <div class="col-span-full">
                                            <label class="block text-[11px] font-black uppercase tracking-widest text-slate-400 mb-2">Calculation Method</label>
                                            <select wire:model.live="productForm.calculation_method" :disabled="!$wire.isEditing" class="block w-full rounded-2xl border-slate-200 py-3 shadow-sm focus:ring-2 focus:ring-indigo-600 transition-all sm:text-sm appearance-none bg-no-repeat bg-[right_1rem_center] bg-[length:1em] disabled:bg-slate-50 disabled:text-slate-500">
                                                <option value="standard">Standard (Qty * Unit Price)</option>
                                                <option value="area">Area Based (L * W * Qty * Unit Price)</option>
                                                <option value="area_lh">Area Based (L * H * Qty * Unit Price)</option>
                                                <option value="volume">Volume Based (L * W * H * Qty * Unit Price)</option>
                                                <option value="formula">Formula (Custom Expression)</option>
                                            </select>
                                        </div>

                                        @if($productForm['calculation_method'] === 'formula')
                                            <div class="col-span-full">
                                                <label class="block text-[11px] font-black uppercase tracking-widest text-slate-400 mb-2">Custom Formula <span class="text-rose-500">*</span></label>
                                                <textarea wire:model="productForm.formula" :disabled="!$wire.isEditing" rows="3" placeholder="e.g. (L * W * H) / 144" class="block w-full rounded-2xl border-slate-200 py-3 shadow-sm focus:ring-2 focus:ring-indigo-600 transition-all sm:text-sm disabled:bg-slate-50 disabled:text-slate-500"></textarea>
                                                <p class="mt-2 text-[10px] text-slate-400 font-bold uppercase tracking-wider">Available variables: L, W, H, Qty</p>
                                                @error('productForm.formula') <span class="text-[11px] text-rose-500 font-bold mt-1 block">{{ $message }}</span> @enderror
                                            </div>
                                        @endif

                                        <div class="col-span-full grid grid-cols-4 gap-4">
                                            <div>
                                                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Length</label>
                                                <input type="number" step="0.01" wire:model="productForm.dimensions.length" :disabled="!$wire.isEditing" class="block w-full rounded-xl border-slate-200 py-2 sm:text-sm disabled:bg-slate-50 disabled:text-slate-500">
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Width</label>
                                                <input type="number" step="0.01" wire:model="productForm.dimensions.width" :disabled="!$wire.isEditing" class="block w-full rounded-xl border-slate-200 py-2 sm:text-sm disabled:bg-slate-50 disabled:text-slate-500">
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Height</label>
                                                <input type="number" step="0.01" wire:model="productForm.dimensions.height" :disabled="!$wire.isEditing" class="block w-full rounded-xl border-slate-200 py-2 sm:text-sm disabled:bg-slate-50 disabled:text-slate-500">
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Unit</label>
                                                <select wire:model="productForm.dimensions.unit" :disabled="!$wire.isEditing" class="block w-full rounded-xl border-slate-200 py-2 text-xs uppercase font-bold disabled:bg-slate-50 disabled:text-slate-500">
                                                    <option value="in">in</option>
                                                    <option value="ft">ft</option>
                                                    <option value="cm">cm</option>
                                                    <option value="m">m</option>
                                                    <option value="mm">mm</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: Variants, Attributes, Images -->
                            <div class="space-y-10">
                                <div>
                                    <div class="flex items-center justify-between mb-6">
                                        <h4 class="text-xs font-black text-indigo-600 uppercase tracking-widest">Product Variants</h4>
                                        <template x-if="$wire.isEditing">
                                            <button type="button" wire:click="addOptionGroup" class="text-[10px] font-black uppercase text-indigo-600 hover:text-indigo-800 tracking-wider">+ Add Group</button>
                                        </template>
                                    </div>
                                    
                                    <div class="space-y-4">
                                        @foreach($options as $optIndex => $opt)
                                            <div class="p-5 rounded-2xl bg-slate-50 border border-slate-100 relative group/opt">
                                                <template x-if="$wire.isEditing">
                                                    <button type="button" wire:click="removeOptionGroup({{ $optIndex }})" class="absolute top-4 right-4 text-slate-300 hover:text-rose-500 transition-colors">
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke_width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                                                    </button>
                                                </template>
                                                <input type="text" wire:model="options.{{ $optIndex }}.name" :disabled="!$wire.isEditing" placeholder="Option Name (e.g. Size)" class="block w-full max-w-xs rounded-xl border-slate-200 py-2 text-sm font-bold mb-4 disabled:bg-white disabled:border-transparent">
                                                
                                                <div class="space-y-3">
                                                    @foreach($opt['values'] as $valIndex => $val)
                                                        <div class="flex items-center gap-3">
                                                            <input type="text" wire:model="options.{{ $optIndex }}.values.{{ $valIndex }}.value" :disabled="!$wire.isEditing" placeholder="Value (e.g. Small)" class="flex-1 rounded-xl border-slate-200 py-2 text-sm disabled:bg-white disabled:border-transparent">
                                                            <div class="relative w-32">
                                                                <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 text-xs">+₹</span>
                                                                <input type="number" step="0.01" wire:model="options.{{ $optIndex }}.values.{{ $valIndex }}.price_adjustment" :disabled="!$wire.isEditing" class="block w-full rounded-xl border-slate-200 py-2 pl-8 text-sm disabled:bg-white disabled:border-transparent">
                                                            </div>
                                                            <template x-if="$wire.isEditing">
                                                                <button type="button" wire:click="removeOptionValue({{ $optIndex }}, {{ $valIndex }})" class="text-slate-300 hover:text-rose-500">
                                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke_width="2.5" d="M6 18L18 6" /></svg>
                                                                </button>
                                                            </template>
                                                        </div>
                                                    @endforeach
                                                    <template x-if="$wire.isEditing">
                                                        <button type="button" wire:click="addOptionValue({{ $optIndex }})" class="text-[10px] font-black uppercase text-indigo-500 tracking-wider">+ Add Value</button>
                                                    </template>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div>
                                    <div class="flex items-center justify-between mb-6">
                                        <h4 class="text-xs font-black text-indigo-600 uppercase tracking-widest">Additional Specs</h4>
                                        <template x-if="$wire.isEditing">
                                            <button type="button" wire:click="addAttribute" class="text-[10px] font-black uppercase text-indigo-600 hover:text-indigo-800 tracking-wider">+ Add Field</button>
                                        </template>
                                    </div>
                                    <div class="space-y-3">
                                        @foreach($extraAttributes as $attrIndex => $attr)
                                            <div class="flex gap-3">
                                                <input type="text" wire:model="extraAttributes.{{ $attrIndex }}.key" :disabled="!$wire.isEditing" placeholder="Key (e.g. Finish)" class="flex-1 rounded-xl border-slate-200 py-2 text-sm disabled:bg-slate-50">
                                                <input type="text" wire:model="extraAttributes.{{ $attrIndex }}.value" :disabled="!$wire.isEditing" placeholder="Value (e.g. Matte)" class="flex-1 rounded-xl border-slate-200 py-2 text-sm disabled:bg-slate-50">
                                                <template x-if="$wire.isEditing">
                                                    <button type="button" wire:click="removeAttribute({{ $attrIndex }})" class="text-slate-300 hover:text-rose-500">
                                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke_width="2" d="M6 18L18 6" /></svg>
                                                    </button>
                                                </template>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div>
                                    <h4 class="text-xs font-black text-indigo-600 uppercase tracking-widest mb-6">Product Media</h4>
                                    <!-- Existing Images -->
                                    @if(!empty($existingImages))
                                        <div class="grid grid-cols-4 gap-4 mb-6">
                                            @foreach($existingImages as $img)
                                                <div class="relative group aspect-square rounded-2xl overflow-hidden border border-slate-200 shadow-sm">
                                                    <img src="{{ $img['url'] }}" class="h-full w-full object-cover">
                                                    <template x-if="$wire.isEditing">
                                                        <button type="button" wire:click="deleteImage({{ $img['id'] }})" class="absolute inset-0 bg-rose-500/80 flex items-center justify-center text-white opacity-0 group-hover:opacity-100 transition-opacity">
                                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke_width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                        </button>
                                                    </template>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    <!-- Upload Area -->
                                    <template x-if="$wire.isEditing">
                                        <div class="relative group">
                                            <label class="flex flex-col items-center px-6 py-8 border-2 border-dashed border-slate-200 rounded-3xl bg-slate-50/50 transition-all hover:bg-white hover:border-indigo-400 cursor-pointer">
                                                <svg class="h-8 w-8 text-slate-300 mb-3 group-hover:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke_width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                                                <div class="text-xs font-black text-slate-600 uppercase tracking-widest">Upload New Photos</div>
                                                <input type="file" multiple wire:model="newImages" class="sr-only">
                                            </label>
                                            <div wire:loading wire:target="newImages" class="absolute inset-0 bg-white/60 flex items-center justify-center rounded-3xl z-10">
                                                <svg class="animate-spin h-6 w-6 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke_width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            </div>
                                        </div>
                                    </template>
                                    @foreach($newImages as $img)
                                        <div class="mt-2 text-[10px] font-black text-emerald-600 uppercase flex items-center gap-2">
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M5 13l4 4L19 7" /></svg>
                                            Ready to upload: {{ $img->getClientOriginalName() }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-8 py-6 bg-slate-50/50 flex flex-col sm:flex-row-reverse gap-4 rounded-b-3xl border-t border-slate-50">
                        @if($isEditing)
                            <button wire:click="saveProduct" type="button"
                                class="px-12 py-3.5 rounded-2xl bg-indigo-600 text-white text-sm font-black shadow-xl shadow-indigo-100 hover:bg-indigo-700 transition-all active:scale-95 disabled:opacity-50"
                                wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="saveProduct">{{ $editingProduct ? 'Save Changes' : 'Create Product' }}</span>
                                <span wire:loading wire:target="saveProduct" class="flex items-center">
                                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke_width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Processing...
                                </span>
                            </button>
                            <button @click="$wire.isEditing = false" type="button"
                                class="px-10 py-3.5 rounded-2xl bg-white border border-slate-200 text-slate-700 text-sm font-black hover:bg-slate-50 transition-all active:scale-95">
                                Discard Changes
                            </button>
                        @else
                            <button wire:click="setEditing(true)" type="button"
                                class="px-12 py-3.5 rounded-2xl bg-indigo-600 text-white text-sm font-black shadow-xl shadow-indigo-100 hover:bg-indigo-700 transition-all active:scale-95">
                                Edit Product
                            </button>
                            <button @click="showEditModal = false" type="button"
                                class="px-10 py-3.5 rounded-2xl bg-white border border-slate-200 text-slate-700 text-sm font-black hover:bg-slate-50 transition-all active:scale-95">
                                Close
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Retire Modal -->
    <div x-show="showRetireModal" class="relative z-[60]" style="display: none;">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[55]"></div>

        <div class="fixed inset-0 z-[60] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div x-show="showRetireModal" class="relative bg-white rounded-3xl p-8 shadow-2xl w-full max-w-md border border-slate-100"
                    @click.away="showRetireModal = false">
                    <div class="h-14 w-14 rounded-2xl bg-rose-50 flex items-center justify-center text-rose-500 mb-6">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke_width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </div>
                    <h3 class="text-2xl font-black text-slate-900 mb-3 tracking-tight">Retire Product?</h3>
                    <p class="text-slate-500 font-medium mb-6">This product will be hidden from new estimates. Existing estimates will not be affected.</p>
                    
                    <label class="block text-[11px] font-black uppercase tracking-widest text-slate-400 mb-2">Reason for Retirement</label>
                    <textarea wire:model="retireReason" rows="3" placeholder="Discontinued, Out of stock, etc." class="block w-full rounded-2xl border-slate-200 py-3 shadow-sm focus:ring-2 focus:ring-rose-500 transition-all sm:text-sm mb-8"></textarea>

                    <div class="flex flex-col gap-3">
                        <button wire:click="retireProduct($wire.activeProductId, $wire.retireReason)"
                            class="w-full py-4 rounded-2xl bg-rose-600 text-white font-black text-sm shadow-xl shadow-rose-100 hover:bg-rose-700 transition-all active:scale-95">
                            Confirm Retirement
                        </button>
                        <button @click="showRetireModal = false; $wire.set('retireReason', '')"
                            class="w-full py-4 rounded-2xl bg-white border border-slate-200 text-slate-600 font-black text-sm hover:bg-slate-50 transition-all">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Upload Modal -->
    <div x-show="showUploadModal" class="relative z-[60]" style="display: none;">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[55]"></div>

        <div class="fixed inset-0 z-[60] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <form action="{{ route('products.import') }}" method="POST" enctype="multipart/form-data"
                    class="relative bg-white rounded-3xl p-8 shadow-2xl w-full max-w-lg border border-slate-100"
                    @click.away="showUploadModal = false"
                    x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                    @csrf
                    <div class="h-14 w-14 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600 mb-6 font-black shadow-sm">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke_width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" /></svg>
                    </div>
                    <h3 class="text-2xl font-black text-slate-900 mb-2 tracking-tight">Bulk Import Products</h3>
                    <p class="text-slate-500 font-medium mb-6">Upload a CSV file to update or add products in bulk.</p>
                    
                    <a href="{{ route('products.template') }}" class="mb-6 p-4 rounded-2xl bg-indigo-50/50 border border-indigo-100 flex items-center justify-between group cursor-pointer hover:bg-indigo-50 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 flex items-center justify-center rounded-xl bg-white text-indigo-600 shadow-sm transition-transform group-hover:scale-110">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                            </div>
                            <div>
                                <div class="text-sm font-black text-slate-900 uppercase tracking-widest">Need a Template?</div>
                                <div class="text-xs text-indigo-600 font-bold underline decoration-2 underline-offset-4">Download CSV Template &rarr;</div>
                            </div>
                        </div>
                    </a>

                    <div class="relative group">
                        <div class="flex flex-col items-center px-6 py-10 border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50 transition-all group-hover:bg-slate-100 group-hover:border-indigo-300">
                            <svg class="h-10 w-10 text-slate-300 mb-4 group-hover:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke_width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                            <div class="text-sm font-bold text-slate-600">
                                <label class="cursor-pointer text-indigo-600 hover:text-indigo-800 transition-colors">
                                    Choose a file
                                    <input type="file" name="csv_file" class="sr-only" required accept=".csv">
                                </label>
                                <span class="text-slate-400">or drag here</span>
                            </div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-3">CSV only (Max 5MB)</p>
                        </div>
                    </div>

                    <div class="mt-8 flex flex-col sm:flex-row-reverse gap-3">
                        <button type="submit" class="flex-1 py-4 px-8 rounded-2xl bg-indigo-600 text-white font-black text-sm shadow-xl shadow-indigo-100 hover:bg-indigo-700 transition-all active:scale-95">
                            Start Import
                        </button>
                        <button type="button" @click="showUploadModal = false" class="flex-1 py-4 px-8 rounded-2xl bg-white border border-slate-200 text-slate-600 font-black text-sm hover:bg-slate-50 transition-all">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <style>
        @keyframes progress {
            0% { transform: translateX(-100%) scaleX(0.2); }
            50% { transform: translateX(0) scaleX(0.5); }
            100% { transform: translateX(100%) scaleX(0.2); }
        }
        .animate-progress {
            animation: progress 2s infinite ease-in-out;
        }
        .ql-toolbar.ql-snow {
            border: none !important;
            border-bottom: 1px solid #f1f5f9 !important;
            background: #f8fafc;
            border-radius: 1rem 1rem 0 0;
        }
        .ql-container.ql-snow {
            border: none !important;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-2px); }
            20%, 40%, 60%, 80% { transform: translateX(2px); }
        }
        .animate-shake {
            animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
        }
    </style>
    <script>
        document.addEventListener('livewire:initialized', () => {
            console.log('Livewire initialized');
            
            Livewire.on('error', (error) => {
                console.error('Livewire Error:', error);
            });
            
            Livewire.hook('request', ({ respond, fail }) => {
                respond(({ status, response }) => {
                    if (status >= 400) {
                        console.error('Request failed with status:', status);
                    }
                });

                fail(({ error }) => {
                    console.error('Request failed:', error);
                });
            });
        });
    </script>
</div>

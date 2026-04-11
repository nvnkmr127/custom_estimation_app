<x-app-layout>
    <div x-data="{}" class="min-h-screen pb-12">
        <!-- Header Section -->
        <div class="mb-10">
            <div class="flex items-center gap-4 mb-4">
                <a href="{{ route('categories.index') }}" class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <div>
                    <nav class="flex mb-1" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-1 md:space-x-3">
                            <li class="inline-flex items-center">
                                <a href="{{ route('categories.index') }}" class="text-xs font-bold text-slate-400 uppercase tracking-widest hover:text-indigo-600 transition-colors">Categories</a>
                            </li>
                            @if($category->parent)
                            <li>
                                <div class="flex items-center">
                                    <svg class="w-3 h-3 text-slate-300 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                                    </svg>
                                    <a href="{{ route('categories.show', $category->parent) }}" class="text-xs font-bold text-slate-400 uppercase tracking-widest hover:text-indigo-600 transition-colors">{{ $category->parent->name }}</a>
                                </div>
                            </li>
                            @endif
                        </ol>
                    </nav>
                    <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ $category->name }}</h1>
                </div>
            </div>

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm transition-all hover:shadow-md">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                         <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-100 uppercase tracking-wider">
                            {{ $category->products->count() }} Products
                        </span>
                        @if($category->description)
                            <p class="text-slate-500 font-medium">{{ $category->description }}</p>
                        @endif
                    </div>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('categories.edit', $category) }}" class="inline-flex items-center px-4 py-2.5 rounded-xl border-2 border-slate-200 bg-white text-slate-700 text-sm font-bold hover:bg-slate-50 hover:border-slate-300 transition-all active:scale-95">
                        Edit Category
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Products List -->
            <div class="lg:col-span-2 space-y-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-black text-slate-900 flex items-center gap-2">
                        <svg class="h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        Products in This Category
                    </h2>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50/50">
                            <tr>
                                <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Product</th>
                                <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">SKU</th>
                                <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Base Price</th>
                                <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 bg-white">
                            @forelse($category->products as $product)
                            <tr class="hover:bg-slate-50/50 transition-all group">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        @if($product->primary_image_url)
                                            <img src="{{ $product->primary_image_url }}" class="h-10 w-10 rounded-lg object-cover border border-slate-200 shadow-sm" alt="">
                                        @else
                                            <div class="h-10 w-10 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            </div>
                                        @endif
                                        <span class="text-sm font-bold text-slate-900">{{ $product->name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-500 font-medium">{{ $product->sku ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm font-bold text-slate-900 text-right">{{ number_format($product->unit_price, 2) }}</td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('products.edit', $product) }}" class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all inline-block">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-slate-400 font-medium">No products assigned to this category yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Sidebar / Hierarchy -->
            <div class="space-y-8">
                <div>
                    <h2 class="text-lg font-black text-slate-900 mb-4 flex items-center gap-2">
                         <svg class="h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        Sub-categories
                    </h2>
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-2">
                        @forelse($category->children as $child)
                            <a href="{{ route('categories.show', $child) }}" class="flex items-center justify-between p-3 rounded-xl hover:bg-slate-50 transition-all group">
                                <span class="text-sm font-bold text-slate-700 group-hover:text-indigo-600">{{ $child->name }}</span>
                                <svg class="h-4 w-4 text-slate-300 group-hover:text-indigo-400 group-hover:translate-x-1 transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        @empty
                            <p class="p-4 text-sm text-slate-400 italic">No sub-categories.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

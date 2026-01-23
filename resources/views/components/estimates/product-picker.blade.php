<div x-show="productPicker.isOpen" class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true"
    style="display: none;">
    <div x-show="productPicker.isOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-500/75 transition-opacity">
    </div>

    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div x-show="productPicker.isOpen" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                @click.away="productPicker.isOpen = false"
                class="relative transform overflow-hidden rounded-xl bg-white px-4 pb-4 pt-5 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">

                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-slate-900">Add Item to Estimate</h3>
                        <button type="button" @click="productPicker.isOpen = false"
                            class="text-slate-400 hover:text-slate-500">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Category Filter -->
                    <div class="mb-4">
                        <select x-model="productPicker.categoryId"
                            class="block w-full rounded-lg border-slate-300 py-2.5 text-slate-900 shadow-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                            <option value="">All Categories</option>
                            <template x-for="category in categories" :key="category.id">
                                <option :value="category.id" x-text="category.name"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Search Box -->
                    <div class="relative mb-6">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input type="text" x-model="productPicker.search"
                            class="block w-full rounded-lg border-0 py-2.5 pl-10 text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm"
                            placeholder="Search products...">
                    </div>

                    <!-- Product List -->
                    <div class="max-h-60 overflow-y-auto space-y-2 mb-6 custom-scrollbar">
                        <template x-for="product in filteredProducts" :key="product.id">
                            <button type="button" @click="selectProduct(product)"
                                class="w-full text-left px-4 py-3 rounded-lg hover:bg-slate-50 border border-transparent hover:border-slate-200 transition-all flex justify-between items-center group">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="h-12 w-12 rounded-lg bg-slate-100 flex-shrink-0 overflow-hidden ring-1 ring-slate-200">
                                        <template x-if="product.images && product.images.length > 0">
                                            <img :src="product.images[0].image_path.startsWith('http') ? product.images[0].image_path : '/storage/' + product.images[0].image_path"
                                                class="h-full w-full object-cover">
                                        </template>
                                        <template x-if="!product.images || product.images.length === 0">
                                            <div class="h-full w-full flex items-center justify-center">
                                                <svg class="h-6 w-6 text-slate-300" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                        </template>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-slate-900" x-text="product.name">
                                        </div>
                                        <div class="text-xs text-slate-500" x-text="product.sku || 'No SKU'">
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-bold text-indigo-600" x-text="product.unit_price">
                                    </div>
                                    <div class="text-[10px] text-slate-400"
                                        x-text="'Per ' + (product.unit_type || 'nos')">
                                    </div>
                                </div>
                            </button>
                        </template>

                        <div x-show="filteredProducts.length === 0" class="text-center py-4 text-slate-500 text-sm">
                            No products found matching your search.
                        </div>
                    </div>

                    <!-- Add Custom Option -->
                    <div class="border-t border-slate-100 pt-4">
                        <button type="button" @click="addCustomItem()"
                            class="w-full flex items-center justify-center gap-2 rounded-lg bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900 hover:bg-slate-100 border border-slate-200 transition-all">
                            <svg class="h-5 w-5 text-slate-500" viewBox="0 0 20 20" fill="currentColor">
                                <path
                                    d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                            </svg>
                            Add Custom Item (Manual Entry)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
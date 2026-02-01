<x-app-layout>
    <div class="max-w-[1200px] mx-auto px-4 py-10"
        x-data='templateForm({{ $products->toJson() }}, {{ isset($template) && $template->items ? json_encode($template->items) : "[]" }}, {{ $unitTypes->toJson() }})'>

        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6 mb-10">
            <div>
                <nav
                    class="flex items-center space-x-2 text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400 mb-2">
                    <a href="{{ route('templates.index') }}" class="hover:text-blue-600 transition-colors">Templates</a>
                    <span class="text-slate-300">
                        <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4">
                            <path d="M9 5l7 7-7 7" />
                        </svg>
                    </span>
                    <span class="text-slate-500">Edit Template</span>
                </nav>
                <div class="flex items-center gap-4">
                    <h1 class="text-3xl font-bold text-slate-900 tracking-tight">
                        Edit Room Template
                    </h1>
                    <span
                        class="px-3 py-1 bg-slate-100 text-slate-500 text-[10px] font-black uppercase tracking-widest rounded-full"
                        x-text="items.length + ' SKUs'"></span>
                </div>
            </div>

            <div class="flex items-center gap-6">
                <a href="{{ route('templates.index') }}"
                    class="text-sm font-bold text-slate-400 hover:text-slate-600 transition-colors">
                    Cancel
                </a>
                <button type="submit" form="template-form"
                    class="inline-flex items-center px-8 py-3 rounded-xl bg-blue-600 text-white text-sm font-bold shadow-lg shadow-blue-200 hover:bg-blue-700 transition-all active:scale-95 group">
                    <svg class="mr-2 h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z" />
                    </svg>
                    Save Changes
                </button>
            </div>
        </div>

        <form id="template-form"
            action="{{ isset($template) ? route('templates.update', $template) : route('templates.store') }}"
            method="POST" class="space-y-8">
            @csrf
            @if(isset($template))
                @method('PUT')
            @endif

            <!-- Template Configuration Card -->
            <div class="bg-white rounded-[2rem] p-10 border border-slate-100 shadow-sm">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-12">
                    <!-- Left Column: Basic Info -->
                    <div class="md:col-span-7 space-y-8">
                        <div>
                            <label for="name"
                                class="block text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-3">Room
                                Name</label>
                            <input type="text" name="name" id="name" required
                                value="{{ old('name', $template->name ?? '') }}" placeholder="e.g. Home Office Setup"
                                class="block w-full rounded-xl border-slate-200 bg-white py-3.5 px-5 text-sm font-semibold text-slate-800 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all placeholder:text-slate-300">
                        </div>
                        <div>
                            <label for="description"
                                class="block text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-3">Notes
                                & Instructions</label>
                            <textarea name="description" id="description" rows="5"
                                placeholder="Modern productive workspace with ergonomic focus and integrated smart lighting."
                                class="block w-full rounded-xl border-slate-200 bg-white py-3.5 px-5 text-sm font-medium text-slate-600 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all placeholder:text-slate-300">{{ old('description', $template->description ?? '') }}</textarea>
                        </div>
                    </div>

                    <!-- Right Column: Unit Types -->
                    <div class="md:col-span-5 border-l border-slate-50 pl-12 flex flex-col">
                        <label
                            class="block text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-6">Applicable
                            Unit Types</label>
                        <div class="flex-1 bg-slate-50/30 rounded-3xl border border-slate-100 p-8">
                            <div class="space-y-4 max-h-[220px] overflow-y-auto custom-scrollbar pr-4">
                                <template x-for="unitType in unitTypes" :key="unitType.id">
                                    <label class="flex items-center gap-4 cursor-pointer group">
                                        <div class="relative flex items-center">
                                            <input type="checkbox" :value="String(unitType.id)"
                                                x-model="allowedUnitTypes"
                                                class="w-5 h-5 rounded border-slate-300 text-blue-600 focus:ring-blue-500 transition-all">
                                        </div>
                                        <span
                                            class="text-sm font-bold text-slate-700 group-hover:text-slate-950 transition-colors"
                                            x-text="unitType.name"></span>
                                    </label>
                                </template>
                            </div>
                            <button type="button"
                                class="mt-8 flex items-center gap-2 text-[10px] font-black text-slate-400 hover:text-blue-600 uppercase tracking-widest transition-colors group">
                                <svg class="h-4 w-4 text-slate-300 group-hover:text-blue-500 transition-colors"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path d="M12 4v16m8-8H4" />
                                </svg>
                                Add Unit Type
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Hidden inputs for unit types -->
                <template x-for="(typeId, index) in allowedUnitTypes" :key="'allowed-'+index">
                    <input type="hidden" :name="`allowed_unit_types[${index}]`" :value="typeId">
                </template>
            </div>

            <!-- Items Editor Section -->
            <div class="bg-white rounded-[2rem] border border-slate-100 shadow-sm overflow-hidden">
                <div class="p-10 border-b border-slate-50 flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Room Items</h2>
                        <p class="text-sm text-slate-400 font-medium mt-1">Manage products and configurations for this
                            room</p>
                    </div>
                    <button type="button" @click="openProductPicker()"
                        class="inline-flex items-center px-8 py-3 rounded-xl bg-blue-600 text-white text-sm font-bold shadow-lg shadow-blue-100 hover:bg-blue-700 transition-all active:scale-95 group">
                        <svg class="mr-2 h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z" />
                        </svg>
                        Browse Library
                    </button>
                </div>

                <!-- Items Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead>
                            <tr class="bg-slate-50/50">
                                <th
                                    class="px-10 py-5 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                    Product Information</th>
                                <th
                                    class="px-6 py-5 text-center text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] w-32">
                                    Quantity</th>
                                <th
                                    class="px-6 py-5 text-center text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] w-48">
                                    Dimensions (L×W×H)</th>
                                <th
                                    class="px-6 py-5 text-center text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] w-40">
                                    Source</th>
                                <th
                                    class="px-6 py-5 text-right text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] w-40">
                                    Unit Price</th>
                                <th class="px-10 py-5 w-16"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <template x-for="(item, index) in isReady ? items : []" :key="'item-'+index">
                                <tr class="group hover:bg-slate-50/30 transition-all">
                                    <!-- Product info -->
                                    <td class="px-10 py-8">
                                        <input type="hidden" :name="`items[${index}][product_id]`"
                                            :value="item.product_id">
                                        <input type="hidden" :name="`items[${index}][unit_type_id]`"
                                            :value="item.unit_type_id">

                                        <div class="flex items-center gap-6">
                                            <div
                                                class="h-20 w-20 rounded-2xl bg-slate-50 border border-slate-100 overflow-hidden flex-shrink-0 flex items-center justify-center">
                                                <template x-if="getProductImage(item.product_id)">
                                                    <img :src="getProductImage(item.product_id)"
                                                        class="h-full w-full object-cover">
                                                </template>
                                                <template x-if="!getProductImage(item.product_id)">
                                                    <svg class="h-8 w-8 text-slate-200" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor">
                                                        <path
                                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                </template>
                                            </div>
                                            <div class="flex flex-col min-w-0">
                                                <input type="text" :name="`items[${index}][item_name]`"
                                                    x-model="item.item_name" required
                                                    class="border-0 p-0 text-lg font-bold text-slate-900 bg-transparent focus:ring-0 placeholder:text-slate-300 tracking-tight"
                                                    placeholder="Teak Wood Plank">

                                                <div class="flex items-center gap-2 mt-1">
                                                    <input type="text" :name="`items[${index}][description]`"
                                                        x-model="item.description"
                                                        class="border-0 p-0 text-sm text-slate-400 font-medium bg-transparent focus:ring-0 placeholder:text-slate-200 truncate max-w-xs"
                                                        placeholder="Premium Grade, Natural Finish">
                                                    <button type="button" @click="viewDescription(item)"
                                                        class="text-slate-300 hover:text-blue-500">
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                            stroke="currentColor" stroke-width="2.5">
                                                            <path
                                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Quantity -->
                                    <td class="px-6 py-8">
                                        <div class="flex justify-center">
                                            <input type="number" step="0.01" :name="`items[${index}][quantity]`"
                                                x-model="item.quantity"
                                                class="w-20 rounded-xl border-slate-200 py-3 text-center text-sm font-bold text-slate-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                        </div>
                                    </td>

                                    <!-- Dimensions -->
                                    <td class="px-6 py-8">
                                        <div class="flex items-center justify-center gap-2">
                                            <div class="flex flex-col items-center">
                                                <span
                                                    class="text-[9px] font-bold text-slate-300 uppercase mb-1">L</span>
                                                <input type="number" step="0.01" :name="`items[${index}][length]`"
                                                    x-model="item.length" @input="calculateQuantity(item)"
                                                    class="w-14 rounded-lg border-slate-200 py-1.5 px-1 text-center text-xs font-bold text-slate-700 bg-white focus:ring-2 focus:ring-blue-500">
                                            </div>
                                            <div class="flex flex-col items-center">
                                                <span
                                                    class="text-[9px] font-bold text-slate-300 uppercase mb-1">W</span>
                                                <input type="number" step="0.01" :name="`items[${index}][width]`"
                                                    x-model="item.width" @input="calculateQuantity(item)"
                                                    class="w-14 rounded-lg border-slate-200 py-1.5 px-1 text-center text-xs font-bold text-slate-700 bg-white focus:ring-2 focus:ring-blue-500">
                                            </div>
                                            <div class="flex flex-col items-center">
                                                <span
                                                    class="text-[9px] font-bold text-slate-300 uppercase mb-1">H</span>
                                                <input type="number" step="0.01" :name="`items[${index}][height]`"
                                                    x-model="item.height" @input="calculateQuantity(item)"
                                                    class="w-14 rounded-lg border-slate-200 py-1.5 px-1 text-center text-xs font-bold text-slate-700 bg-white focus:ring-2 focus:ring-blue-500">
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Source (Unit Type) -->
                                    <td class="px-6 py-8">
                                        <div class="flex flex-col items-center">
                                            <div class="relative w-full">
                                                <select x-model="item.unit_type_id" @change="onUnitTypeChange(item)"
                                                    class="w-full rounded-xl border-slate-200 bg-slate-50/50 py-2.5 px-4 text-xs font-bold text-slate-700 focus:ring-2 focus:ring-blue-500 appearance-none cursor-pointer">
                                                    <option value="">Manual</option>
                                                    <template x-for="ut in getFilteredUnitTypes()" :key="'ut-'+ut.id">
                                                        <option :value="String(ut.id)" x-text="ut.name"></option>
                                                    </template>
                                                </select>
                                                <div
                                                    class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-400">
                                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor" stroke-width="4">
                                                        <path d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="mt-2 relative w-full">
                                                <template x-if="item.unit_type_id">
                                                    <select x-model="item.unit_type"
                                                        :name="`items[${index}][unit_type]`"
                                                        class="w-full border-0 p-0 text-[10px] font-black text-slate-400 uppercase tracking-tighter bg-transparent focus:ring-0 text-center cursor-pointer">
                                                        <template x-for="u in getAvailableUnits(item.unit_type_id)"
                                                            :key="'u-'+u">
                                                            <option :value="u" x-text="u"></option>
                                                        </template>
                                                    </select>
                                                </template>
                                                <template x-if="!item.unit_type_id">
                                                    <input type="text" x-model="item.unit_type"
                                                        :name="`items[${index}][unit_type]`"
                                                        class="w-full border-0 p-0 text-[10px] font-black text-slate-400 uppercase tracking-tighter bg-transparent focus:ring-0 text-center"
                                                        placeholder="NOS">
                                                </template>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Price -->
                                    <td class="px-6 py-8 text-right">
                                        <div class="flex flex-col items-end">
                                            <div class="flex items-center text-lg font-bold text-slate-900">
                                                <span>₹</span>
                                                <input type="number" step="0.01" :name="`items[${index}][unit_price]`"
                                                    x-model="item.unit_price"
                                                    class="border-0 p-0 w-24 text-right bg-transparent focus:ring-0 font-bold">
                                            </div>
                                            <span
                                                class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Base
                                                Cost</span>
                                        </div>
                                    </td>

                                    <!-- Delete -->
                                    <td class="px-10 py-8 text-right">
                                        <button type="button" @click="items.splice(index, 1)"
                                            class="p-2 text-slate-200 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="2.5">
                                                <path
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>

                            <!-- Empty State -->
                            <tr x-show="items.length === 0">
                                <td colspan="6" class="px-10 py-32 text-center">
                                    <div class="max-w-xs mx-auto flex flex-col items-center">
                                        <div
                                            class="h-24 w-24 rounded-full bg-slate-50 flex items-center justify-center text-slate-200 mb-8 border border-slate-100 italic">
                                            <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path
                                                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                            </svg>
                                        </div>
                                        <h4 class="text-xl font-bold text-slate-900 mb-2">Build your template</h4>
                                        <p class="text-sm text-slate-400 font-medium mb-10 leading-relaxed">Add products
                                            from your library to configure this room template.</p>
                                        <button type="button" @click="openProductPicker()"
                                            class="px-10 py-3 rounded-xl bg-blue-600 text-white text-xs font-bold uppercase tracking-widest shadow-lg shadow-blue-100 hover:bg-blue-700 transition-all active:scale-95">
                                            Open Library
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-8 py-6 bg-slate-50/30 border-t border-slate-50 text-center">
                    <button type="button" @click="openProductPicker()"
                        class="text-xs font-black text-slate-500 hover:text-slate-950 transition-colors uppercase tracking-widest">
                        + Click to browse product library
                    </button>
                </div>
            </div>

            <!-- Sticky Footer inside form -->
            <div
                class="fixed bottom-0 left-0 right-0 bg-white/90 backdrop-blur-xl border-t border-slate-100 shadow-[0_-10px_40px_-15px_rgba(0,0,0,0.05)] z-40 px-10 py-6 transition-all">
                <div class="max-w-[1200px] mx-auto flex items-center justify-between">
                    <div class="flex items-center gap-16">
                        <div>
                            <span
                                class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Total
                                SKUs</span>
                            <div class="text-2xl font-black text-slate-900 tabular-nums">
                                <span x-text="items.length < 10 ? '0' + items.length : items.length"></span>
                                <span class="text-sm font-bold text-slate-400 ml-1">items</span>
                            </div>
                        </div>
                        <div class="h-10 w-px bg-slate-100"></div>
                        <div>
                            <span
                                class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Estimated
                                Total</span>
                            <div class="text-2xl font-black text-slate-900 tabular-nums">
                                <span class="text-lg">₹</span><span x-text="number_format(totalAmount)">0.00</span>
                            </div>
                        </div>
                    </div>

                    <button type="submit"
                        class="inline-flex items-center px-12 py-4 rounded-xl bg-blue-600 text-white text-sm font-black shadow-xl shadow-blue-200 hover:bg-blue-700 hover:shadow-blue-300 transition-all active:scale-95 group">
                        Save Template
                    </button>
                </div>
            </div>
        </form>

        <!-- Product Library Modal -->
        <div x-show="productPicker.isOpen" class="relative z-50 shadow-2xl" style="display: none;">
            <div x-show="productPicker.isOpen" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-40"></div>

            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div x-show="productPicker.isOpen" x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        @click.away="productPicker.isOpen = false"
                        class="relative transform overflow-hidden rounded-[2.5rem] bg-white text-left shadow-2xl transition-all w-full max-w-2xl border border-slate-100">

                        <div class="p-8 pb-4 flex items-center justify-between border-b border-slate-50">
                            <div>
                                <h3 class="text-2xl font-bold text-slate-800 tracking-tight">Product Library</h3>
                                <p class="text-sm text-slate-500 font-medium">Search and select products for your
                                    template.</p>
                            </div>
                            <button @click="productPicker.isOpen = false"
                                class="p-2 text-slate-300 hover:text-slate-600 hover:bg-slate-100 rounded-2xl transition-all">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2.5">
                                    <path d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="p-8">
                            <!-- Search -->
                            <div class="relative mb-8">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                    <svg class="h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" />
                                    </svg>
                                </div>
                                <input type="text" x-model="productPicker.search"
                                    class="block w-full rounded-2xl border-slate-200 bg-slate-50/50 py-4 pl-12 pr-4 text-slate-800 placeholder:text-slate-400 focus:ring-2 focus:ring-indigo-600 focus:border-transparent transition-all sm:text-sm shadow-inner"
                                    placeholder="Type name or SKU to filter...">
                            </div>

                            <!-- Product List -->
                            <div class="max-h-[50vh] overflow-y-auto space-y-3 pr-2 scrollbar-none">
                                <template x-for="product in filteredProducts" :key="product.id">
                                    <button type="button" @click="selectProduct(product)"
                                        class="w-full text-left p-4 rounded-3xl bg-white border border-slate-100 hover:border-slate-300 hover:bg-slate-50 transition-all flex justify-between items-center group">
                                        <div class="flex items-center gap-4">
                                            <div
                                                class="h-16 w-16 rounded-2xl bg-slate-100 border border-slate-100 flex-shrink-0 overflow-hidden group-hover:scale-105 transition-transform">
                                                <template x-if="product.images && product.images.length > 0">
                                                    <img :src="product.images[0].image_path.startsWith('http') ? product.images[0].image_path : '/storage/' + product.images[0].image_path"
                                                        class="h-full w-full object-cover">
                                                </template>
                                                <template x-if="!product.images || product.images.length === 0">
                                                    <div
                                                        class="h-full w-full flex items-center justify-center text-slate-300">
                                                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                                                            stroke="currentColor" stroke-width="1.5">
                                                            <path
                                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                        </svg>
                                                    </div>
                                                </template>
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-800 group-hover:text-slate-600 transition-colors"
                                                    x-text="product.name"></div>
                                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1"
                                                    x-text="product.sku || 'NO SKU CODE'"></div>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-lg font-bold text-slate-800">₹<span
                                                    x-text="number_format(product.unit_price)"></span></div>
                                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest"
                                                x-text="'/ ' + (product.unit_type || 'nos')"></div>
                                        </div>
                                    </button>
                                </template>
                                <div x-show="filteredProducts.length === 0"
                                    class="flex flex-col items-center py-12 text-slate-300">
                                    <svg class="h-16 w-16 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="1">
                                        <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <p class="font-bold text-xs uppercase tracking-widest">No matching products</p>
                                </div>
                            </div>
                        </div>

                        <div class="p-8 pt-4 bg-slate-50/50 flex flex-col gap-3">
                            <button type="button" @click="addCustomItem()"
                                class="w-full flex items-center justify-center gap-3 rounded-2xl bg-white px-6 py-4 text-xs font-bold text-slate-600 uppercase tracking-widest hover:bg-slate-100 border border-slate-200 transition-all shadow-sm active:scale-[0.98]">
                                <svg class="h-4 w-4 text-indigo-500" viewBox="0 0 20 20" fill="currentColor">
                                    <path
                                        d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                                </svg>
                                Manual Custom Item
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Configuration Modal -->
        <div x-show="configModal.isOpen" class="relative z-[60]" style="display: none;">
            <div x-show="configModal.isOpen" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                class="fixed inset-0 bg-slate-900/40 backdrop-blur-md z-40"></div>

            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div x-show="configModal.isOpen" x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" @click.away="closeConfigModal()"
                        class="relative transform overflow-hidden rounded-[2.5rem] bg-white text-left shadow-2xl transition-all w-full max-w-lg border border-slate-100">

                        <div x-show="configModal.product">
                            <div class="p-8 pb-4 flex items-center justify-between border-b border-slate-50">
                                <h3 class="text-xl font-bold text-slate-800 tracking-tight"
                                    x-text="'Select Variations for ' + configModal.product?.name"></h3>
                                <button type="button" @click="closeConfigModal()"
                                    class="p-2 text-slate-300 hover:text-slate-600 hover:bg-slate-100 rounded-2xl transition-all">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2.5">
                                        <path d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <div class="p-8 space-y-8 max-h-[60vh] overflow-y-auto">
                                <template x-for="option in configModal.product?.options" :key="'cfg-'+option.id">
                                    <div>
                                        <label
                                            class="block text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-4"
                                            x-text="option.name"></label>
                                        <div class="grid grid-cols-2 gap-3">
                                            <template x-for="value in option.values" :key="'val-'+value.id">
                                                <div @click="configModal.options[option.id] = value.id"
                                                    :class="{'ring-2 ring-indigo-600 border-transparent bg-indigo-50/50 shadow-indigo-100': configModal.options[option.id] === value.id, 'border-slate-200 hover:border-indigo-400 bg-white shadow-sm': configModal.options[option.id] !== value.id}"
                                                    class="cursor-pointer relative flex flex-col items-center justify-center rounded-2xl border p-4 transition-all hover:shadow-md group">

                                                    <span class="text-sm font-bold text-slate-800 transition-colors"
                                                        :class="{'text-indigo-700': configModal.options[option.id] === value.id}"
                                                        x-text="value.value"></span>

                                                    <span x-show="value.price_adjustment != 0"
                                                        class="mt-1 text-[10px] font-bold uppercase tracking-tighter transition-colors"
                                                        :class="configModal.options[option.id] === value.id ? 'text-indigo-500' : 'text-slate-400'"
                                                        x-text="(value.price_adjustment > 0 ? '+ ₹' : '- ₹') + Math.abs(value.price_adjustment)">
                                                    </span>

                                                    <div x-show="configModal.options[option.id] === value.id"
                                                        class="absolute top-2 right-2 h-4 w-4 text-indigo-600">
                                                        <svg fill="currentColor" viewBox="0 0 20 20">
                                                            <path
                                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" />
                                                        </svg>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div class="p-8 bg-slate-50 border-t border-slate-100">
                                <button type="button" @click="confirmConfig()"
                                    class="w-full py-4 rounded-2xl bg-slate-950 text-white text-sm font-black shadow-xl shadow-slate-200 hover:bg-black transition-all active:scale-95 flex items-center justify-center gap-3">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2.5">
                                        <path d="M5 13l4 4L19 7" />
                                    </svg>
                                    Add this Configuration
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Description Viewer Modal -->
        <div x-show="descriptionModal.isOpen" class="relative z-[70]" style="display: none;">
            <div x-show="descriptionModal.isOpen" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                class="fixed inset-0 bg-slate-950/40 backdrop-blur-md z-40"></div>

            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div x-show="descriptionModal.isOpen" x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        @click.away="descriptionModal.isOpen = false"
                        class="relative transform overflow-hidden rounded-[2.5rem] bg-white text-left shadow-2xl transition-all w-full max-w-lg border border-slate-100">

                        <div class="p-8 pb-4 flex items-center justify-between border-b border-slate-50">
                            <h3 class="text-xl font-black text-slate-950 tracking-tight"
                                x-text="descriptionModal.title">
                            </h3>
                            <button type="button" @click="descriptionModal.isOpen = false"
                                class="p-2 text-slate-300 hover:text-slate-600 hover:bg-slate-100 rounded-2xl transition-all">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2.5">
                                    <path d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="p-8">
                            <label
                                class="block text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 mb-4">Edit
                                Description</label>
                            <textarea x-model="descriptionModal.text" rows="8"
                                class="block w-full rounded-[1.5rem] border-slate-200 bg-slate-50/50 py-4 px-5 text-slate-600 text-sm leading-relaxed font-medium focus:ring-2 focus:ring-slate-950 focus:bg-white transition-all shadow-inner placeholder:text-slate-300"
                                placeholder="Enter detailed specifications..."></textarea>
                        </div>

                        <div class="p-8 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-4">
                            <button type="button" @click="descriptionModal.isOpen = false"
                                class="px-6 py-3 text-xs font-black text-slate-400 uppercase tracking-widest hover:text-slate-600 transition-colors">
                                Cancel
                            </button>
                            <button type="button" @click="saveModalDescription()"
                                class="px-10 py-3 rounded-2xl bg-slate-950 text-white text-xs font-black uppercase tracking-widest shadow-xl shadow-slate-200 hover:bg-black transition-all active:scale-95 flex items-center gap-2">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="3">
                                    <path d="M5 13l4 4L19 7" />
                                </svg>
                                Save Changes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Script copy of logic with small enhancements -->
    <script>
        function templateForm(products, initialItems, unitTypes) {
            return {
                products: products,
                items: initialItems.length ? initialItems : [],
                unitTypes: unitTypes,
                allowedUnitTypes: [],
                isReady: false,

                productPicker: { isOpen: false, search: '' },
                configModal: { isOpen: false, product: null, options: {}, basePrice: 0, activeItem: null, isNewItem: false },
                descriptionModal: { isOpen: false, text: '', title: '', activeItem: null },

                init() {
                    const templateData = @json(isset($template) && $template->allowed_unit_types ? $template->allowed_unit_types : []);
                    this.allowedUnitTypes = templateData.map(id => String(id));

                    this.items.forEach(item => {
                        if (!item.item_name && item.name) item.item_name = item.name;
                        if (!item.options) item.options = [];
                        if (item.unit_type_id) {
                            item.unit_type_id = String(item.unit_type_id);
                        } else {
                            item.unit_type_id = '';
                        }

                        // Hydrate calculation logic
                        if (item.product_id) {
                            const p = this.products.find(x => x.id == item.product_id);
                            if (p && p.calculation_method) {
                                item.calculation_method = p.calculation_method;
                                item.formula_string = p.formula;
                            }
                        }
                    });
                    this.isReady = true;
                },

                get totalAmount() {
                    return this.items.reduce((sum, item) => sum + (parseFloat(item.quantity || 0) * parseFloat(item.unit_price || 0)), 0);
                },

                calculateQuantity(item) {
                    const l = parseFloat(item.length) || 0;
                    const w = parseFloat(item.width) || 0;
                    const h = parseFloat(item.height) || 0;

                    let method = item.calculation_method || 'nos';
                    let formulaStr = item.formula_string || '';

                    if (!item.calculation_method && item.product_id) {
                        const p = this.products.find(x => x.id == item.product_id);
                        if (p) {
                            method = p.calculation_method || 'nos';
                            formulaStr = p.formula || '';
                        }
                    }

                    if (method === 'area') {
                        item.quantity = (l * w).toFixed(2);
                    } else if (method === 'volume') {
                        item.quantity = (l * w * h).toFixed(2);
                    } else if (method === 'area_lh') {
                        item.quantity = (l * h).toFixed(2);
                    } else if (method === 'formula' && formulaStr) {
                        try {
                            let expression = formulaStr.toLowerCase()
                                .replace(/\bl\b/g, l)
                                .replace(/\bw\b/g, w)
                                .replace(/\bh\b/g, h);

                            if (/^[0-9+\-*/().\s]+$/.test(expression)) {
                                const result = (new Function('return ' + expression))();
                                item.quantity = isNaN(result) ? 0 : parseFloat(result).toFixed(2);
                            }
                        } catch (e) {
                            console.error('Formula evaluation error:', e);
                        }
                    }
                },

                number_format(val) {
                    return new Intl.NumberFormat('en-IN').format(val);
                },

                onUnitTypeChange(item) {
                    if (item.unit_type_id) {
                        const ut = this.unitTypes.find(u => u.id == item.unit_type_id);
                        if (ut && ut.units?.length > 0) item.unit_type = ut.units[0];
                    }
                },

                getAvailableUnits(typeId) {
                    const ut = this.unitTypes.find(u => u.id == typeId);
                    return ut ? ut.units : [];
                },

                getFilteredUnitTypes() {
                    return !this.allowedUnitTypes.length ? this.unitTypes : this.unitTypes.filter(ut => this.allowedUnitTypes.includes(String(ut.id)));
                },

                get filteredProducts() {
                    const s = this.productPicker.search.toLowerCase();
                    return s === '' ? this.products : this.products.filter(p => p.name.toLowerCase().includes(s) || (p.sku && p.sku.toLowerCase().includes(s)));
                },

                openProductPicker() { this.productPicker.isOpen = true; this.productPicker.search = ''; },

                selectProduct(product) {
                    if (product.options?.length > 0) {
                        this.productPicker.isOpen = false;
                        this.openConfigModal(product, null, true);
                    } else {
                        this.items.push(this.createNewItem(product));
                        this.productPicker.isOpen = false;
                    }
                },

                createNewItem(product, options = []) {
                    let price = parseFloat(product.unit_price || 0);
                    options.forEach(o => price += parseFloat(o.price_adjustment || 0));

                    return {
                        product_id: product.id,
                        item_name: product.name,
                        description: product.description || '',
                        quantity: 1,
                        unit_type: product.unit_type || 'nos',
                        unit_type_id: product.unit_type_id ? String(product.unit_type_id) : '',
                        unit_price: price,
                        length: product.dimensions?.length || '',
                        width: product.dimensions?.width || '',
                        height: product.dimensions?.height || '',
                        calculation_method: product.calculation_method || 'nos',
                        formula_string: product.formula || '',
                        options: options
                    };
                },

                addCustomItem() {
                    this.items.push({ product_id: null, item_name: 'Custom Product', description: '', quantity: 1, length: '', width: '', height: '', unit_type: 'nos', unit_type_id: '', unit_price: 0, options: [] });
                    this.productPicker.isOpen = false;
                },

                openConfigModal(product, item, isNew) {
                    this.configModal = { isOpen: true, product, activeItem: item, isNewItem: isNew, basePrice: parseFloat(product.unit_price || 0), options: {} };
                    product.options.forEach(opt => { if (opt.values?.length) this.configModal.options[opt.id] = opt.values[0].id; });
                },

                closeConfigModal() {
                    this.configModal.isOpen = false;
                    if (this.configModal.isNewItem) this.productPicker.isOpen = true;
                },

                confirmConfig() {
                    const p = this.configModal.product;
                    let adj = 0;
                    const selected = [];
                    p.options.forEach(opt => {
                        const val = opt.values.find(v => v.id == this.configModal.options[opt.id]);
                        if (val) {
                            adj += parseFloat(val.price_adjustment || 0);
                            selected.push({ name: opt.name, value: val.value, price_adjustment: val.price_adjustment });
                        }
                    });

                    if (this.configModal.isNewItem) {
                        this.items.push(this.createNewItem(p, selected));
                    } else if (this.configModal.activeItem) {
                        this.configModal.activeItem.unit_price = this.configModal.basePrice + adj;
                        this.configModal.activeItem.options = selected;
                    }
                    this.configModal.isOpen = false;
                },

                getProductImage(pid) {
                    if (!pid) return null;
                    const p = this.products.find(x => x.id == pid);
                    if (p?.images?.length > 0) {
                        const path = p.images[0].image_path;
                        return path.startsWith('http') ? path : '/storage/' + path;
                    }
                    return null;
                },

                viewDescription(item) {
                    this.descriptionModal = {
                        isOpen: true,
                        text: item.description || '',
                        title: item.item_name || 'Item Details',
                        activeItem: item
                    };
                },

                saveModalDescription() {
                    if (this.descriptionModal.activeItem) {
                        this.descriptionModal.activeItem.description = this.descriptionModal.text;
                    }
                    this.descriptionModal.isOpen = false;
                }
            };
        }
    </script>

    <style>
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }

        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</x-app-layout>
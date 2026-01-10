<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8 md:flex md:items-center md:justify-between">
            <div class="min-w-0 flex-1">
                <nav class="flex" aria-label="Breadcrumb">
                    <ol role="list" class="flex items-center space-x-4">
                        <li>
                            <div class="flex items-center">
                                <a href="{{ route('templates.index') }}"
                                    class="text-sm font-medium text-slate-500 hover:text-slate-700">Templates</a>
                            </div>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <svg class="h-5 w-5 flex-shrink-0 text-slate-300" fill="currentColor"
                                    viewBox="0 0 20 20" aria-hidden="true">
                                    <path d="M5.555 17.776l8-16 .894.448-8 16-.894-.448z" />
                                </svg>
                                <span class="ml-4 text-sm font-medium text-slate-900" aria-current="page">
                                    {{ isset($template) ? 'Edit Template' : 'Create Template' }}
                                </span>
                            </div>
                        </li>
                    </ol>
                </nav>
                <h2 class="mt-2 text-2xl font-bold leading-7 text-slate-900 sm:truncate sm:text-3xl sm:tracking-tight">
                    {{ isset($template) ? $template->name : 'New Room Template' }}
                </h2>
            </div>
            <div class="mt-4 flex flex-shrink-0 md:ml-4 md:mt-0">
                <a href="{{ route('templates.index') }}"
                    class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Cancel</a>
                <button type="submit" form="template-form"
                    class="ml-3 inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    Save Template
                </button>
            </div>
        </div>

        <form id="template-form"
            action="{{ isset($template) ? route('templates.update', $template) : route('templates.store') }}"
            method="POST" class="space-y-8"
            x-data='templateForm({{ $products->toJson() }}, {{ isset($template) && $template->items ? json_encode($template->items) : "[]" }}, {{ $unitTypes->toJson() }})'>
            @csrf
            @if(isset($template))
                @method('PUT')
            @endif

            <div class="grid grid-cols-1 gap-x-8 gap-y-8 lg:grid-cols-3">
                <!-- Left Column: Template Details -->
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white shadow-sm ring-1 ring-slate-900/5 sm:rounded-xl px-4 py-6 sm:p-8">
                        <h2 class="text-base font-semibold leading-7 text-slate-900 mb-4">Template Details</h2>
                        <div class="space-y-4">
                            <div>
                                <label for="name"
                                    class="block text-sm font-medium leading-6 text-slate-900">Name</label>
                                <div class="mt-2">
                                    <input type="text" name="name" id="name" required
                                        value="{{ old('name', $template->name ?? '') }}"
                                        placeholder="e.g. Master Bedroom, Kitchen Standard"
                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                </div>
                            </div>
                            <div>
                                <label for="description"
                                    class="block text-sm font-medium leading-6 text-slate-900">Description</label>
                                <div class="mt-2">
                                    <textarea name="description" id="description" rows="4"
                                        placeholder="Describe what's included in this room template..."
                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">{{ old('description', $template->description ?? '') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Unit Type Configuration Box -->
                    <div class="bg-white shadow-sm ring-1 ring-slate-900/5 sm:rounded-xl px-4 py-6 sm:p-8">
                        <h2 class="text-base font-semibold leading-7 text-slate-900 mb-2">Unit Configuration</h2>
                        <p class="text-sm text-slate-500 mb-4">Assign specific unit types (Area, Volume, Count) to items
                            to the right.</p>

                        <div class="space-y-4">
                            <div class="rounded-md bg-indigo-50 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-indigo-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.25v2.75a.75.75 0 001.5 0v-3.75A.75.75 0 0010 9H9z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-indigo-800">Assigning Units</h3>
                                        <div class="mt-2 text-sm text-indigo-700">
                                            <p>Use the dropdowns in the items table to assign a Unit Type (e.g.
                                                <b>Area</b>) and a specific Unit (e.g. <b>sqft</b>). These will be
                                                pre-filled when you load this template into an estimate.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Items -->
                <div class="lg:col-span-2">
                    <div class="bg-white shadow-sm ring-1 ring-slate-900/5 sm:rounded-xl overflow-hidden">
                        <div
                            class="px-4 py-5 sm:px-6 border-b border-slate-200 flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <h3 class="text-base font-semibold leading-6 text-slate-900">Template Items</h3>
                                <p class="mt-1 text-sm text-slate-500">Define the default items and quantities for this
                                    room.</p>
                            </div>
                            <button type="button" @click="openProductPicker()"
                                class="inline-flex items-center gap-x-1.5 rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                <svg class="-ml-0.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path
                                        d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                                </svg>
                                Add Item
                            </button>
                        </div>

                        <!-- Items Table -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <!-- Image -->
                                        <th scope="col"
                                            class="pl-4 pr-3 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider w-16 sm:pl-6">
                                            Image
                                        </th>
                                        <!-- Item Details -->
                                        <th scope="col"
                                            class="px-3 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                            Item
                                        </th>
                                        <!-- Quantity -->
                                        <th scope="col"
                                            class="px-3 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wider w-24">
                                            Qty
                                        </th>
                                        <!-- Unit -->
                                        <th scope="col"
                                            class="px-3 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider w-48">
                                            Unit Type / Unit
                                        </th>
                                        <!-- Unit Price (Optional/For reference) -->
                                        <th scope="col"
                                            class="px-3 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider w-32">
                                            Unit Price
                                        </th>
                                        <!-- Actions -->
                                        <th scope="col" class="relative py-3 pl-3 pr-4 sm:pr-6 w-10">
                                            <span class="sr-only">Actions</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-slate-200">
                                    <template x-for="(item, index) in items" :key="index">
                                        <tr class="group hover:bg-slate-50 transition-colors">
                                            <!-- Image -->
                                            <td class="pl-4 pr-3 py-4 text-sm text-slate-500 sm:pl-6">
                                                <div
                                                    class="h-10 w-10 rounded-lg bg-slate-100 ring-1 ring-slate-200 overflow-hidden flex items-center justify-center">
                                                    <!-- Logic to find image from products list based on ID -->
                                                    <template x-if="getProductImage(item.product_id)">
                                                        <img :src="getProductImage(item.product_id)"
                                                            class="h-full w-full object-cover">
                                                    </template>
                                                    <template x-if="!getProductImage(item.product_id)">
                                                        <svg class="h-5 w-5 text-slate-300" fill="none"
                                                            viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                        </svg>
                                                    </template>
                                                </div>
                                            </td>

                                            <!-- Item Details -->
                                            <td class="px-3 py-4 text-sm font-medium text-slate-900">
                                                <!-- Hidden Product ID input -->
                                                <input type="hidden" :name="`items[${index}][product_id]`"
                                                    :value="item.product_id">

                                                <!-- Item Name Input -->
                                                <input type="text" :name="`items[${index}][item_name]`"
                                                    x-model="item.item_name" required
                                                    class="block w-full border-0 p-0 text-sm font-semibold text-slate-900 placeholder:text-slate-400 focus:ring-0 bg-transparent"
                                                    placeholder="Item Name">

                                                <!-- Description -->
                                                <input type="text" :name="`items[${index}][description]`"
                                                    x-model="item.description"
                                                    class="block w-full border-0 p-0 text-xs text-slate-500 placeholder:text-slate-400 focus:ring-0 bg-transparent mt-1"
                                                    placeholder="Description">

                                                <!-- Selected Options Display -->
                                                <template x-if="item.options && item.options.length > 0">
                                                    <div class="flex flex-wrap gap-1 mt-2">
                                                        <template x-for="(opt, optIndex) in item.options">
                                                            <div
                                                                class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10">
                                                                <span x-text="opt.name + ': ' + opt.value"></span>

                                                                <!-- Hidden Inputs for Options -->
                                                                <input type="hidden"
                                                                    :name="`items[${index}][options][${optIndex}][name]`"
                                                                    :value="opt.name">
                                                                <input type="hidden"
                                                                    :name="`items[${index}][options][${optIndex}][value]`"
                                                                    :value="opt.value">
                                                                <input type="hidden"
                                                                    :name="`items[${index}][options][${optIndex}][price_adjustment]`"
                                                                    :value="opt.price_adjustment">
                                                            </div>
                                                        </template>
                                                    </div>
                                                </template>
                                            </td>

                                            <!-- Qty -->
                                            <td class="px-3 py-4 text-sm text-slate-500">
                                                <input type="number" step="0.01" :name="`items[${index}][quantity]`"
                                                    x-model="item.quantity"
                                                    class="block w-full border-0 p-0 text-sm text-slate-900 text-center focus:ring-0 bg-transparent rounded hover:bg-slate-50">
                                            </td>

                                            <!-- Unit Type / Unit -->
                                            <td class="px-3 py-4 text-sm text-slate-500">
                                                <div class="flex flex-col gap-2">
                                                    <!-- Initial State: Show Button if no unit type assigned -->
                                                    <template x-if="!item.unit_type_id && !item._showTypePicker">
                                                        <button type="button" @click="item._showTypePicker = true"
                                                            class="flex items-center justify-center w-full rounded-xl border-2 border-dashed border-slate-200 bg-slate-50/50 py-2.5 px-3 text-[10px] font-bold text-slate-500 hover:border-indigo-400 hover:text-indigo-600 hover:bg-white hover:shadow-sm transition-all uppercase tracking-widest leading-none">
                                                            <svg class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24"
                                                                stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="3" d="M12 4v16m8-8H4" />
                                                            </svg>
                                                            Unit
                                                        </button>
                                                    </template>

                                                    <!-- Active State: Show Type Selection and Unit Selection -->
                                                    <template x-if="item.unit_type_id || item._showTypePicker">
                                                        <div class="space-y-2">
                                                            <!-- Type Selection -->
                                                            <div class="relative group">
                                                                <select x-model="item.unit_type_id"
                                                                    @change="onUnitTypeChange(item)"
                                                                    class="block w-full rounded-lg border-slate-200 bg-slate-50/50 py-1.5 px-2 text-[10px] font-bold text-slate-600 focus:ring-2 focus:ring-indigo-600 transition-all appearance-none cursor-pointer hover:bg-white">
                                                                    <option value="">Manual</option>
                                                                    <template x-for="ut in unitTypes" :key="ut.id">
                                                                        <option :value="ut.id" x-text="ut.name">
                                                                        </option>
                                                                    </template>
                                                                </select>
                                                                <div
                                                                    class="absolute inset-y-0 right-2 flex items-center pointer-events-none text-slate-400 group-hover:text-slate-600">
                                                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24"
                                                                        stroke="currentColor">
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round" stroke-width="2"
                                                                            d="M19 9l-7 7-7-7" />
                                                                    </svg>
                                                                </div>
                                                            </div>

                                                            <!-- Unit Value Selection -->
                                                            <div class="relative">
                                                                <template x-if="item.unit_type_id">
                                                                    <select x-model="item.unit_type"
                                                                        :name="`items[${index}][unit_type]`"
                                                                        class="block w-full rounded-lg border-indigo-200 bg-indigo-50/30 py-1.5 px-2 text-[11px] font-bold text-indigo-700 focus:ring-2 focus:ring-indigo-600 transition-all appearance-none cursor-pointer hover:bg-indigo-50/50 shadow-sm text-center">
                                                                        <template
                                                                            x-for="u in getAvailableUnits(item.unit_type_id)"
                                                                            :key="u">
                                                                            <option :value="u" x-text="u"></option>
                                                                        </template>
                                                                    </select>
                                                                </template>
                                                                <template x-if="!item.unit_type_id">
                                                                    <input type="text" x-model="item.unit_type"
                                                                        :name="`items[${index}][unit_type]`"
                                                                        placeholder="e.g. nos"
                                                                        class="block w-full rounded-lg border-slate-200 bg-slate-50 py-1.5 px-2 text-[11px] font-bold text-slate-700 text-center focus:ring-2 focus:ring-indigo-600 shadow-sm">
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </template>
                                                    <input type="hidden" :name="`items[${index}][unit_type_id]`"
                                                        :value="item.unit_type_id">
                                                </div>
                                            </td>

                                            <!-- Unit Price -->
                                            <td class="px-3 py-4 text-sm text-slate-500 text-right">
                                                <input type="number" step="0.01" :name="`items[${index}][unit_price]`"
                                                    x-model="item.unit_price"
                                                    class="block w-full border-0 p-0 text-sm text-slate-900 text-right focus:ring-0 bg-transparent rounded hover:bg-slate-50">
                                            </td>

                                            <!-- Remove Action -->
                                            <td class="pl-3 pr-4 py-4 text-right text-sm font-medium sm:pr-6">
                                                <button type="button" @click="items.splice(index, 1)"
                                                    class="text-slate-400 hover:text-rose-600 transition-colors">
                                                    <span class="sr-only">Remove</span>
                                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd"
                                                            d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>

                                    <!-- Empty State -->
                                    <tr x-show="items.length === 0">
                                        <td colspan="6"
                                            class="px-3 py-8 text-center text-sm text-slate-500 border-dashed border-2 border-slate-100 rounded-lg m-4">
                                            <div class="flex flex-col items-center justify-center">
                                                <svg class="h-10 w-10 text-slate-300 mb-2" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="1.5"
                                                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                </svg>
                                                <p>No items in this template yet.</p>
                                                <button type="button" @click="openProductPicker()"
                                                    class="mt-2 text-indigo-600 hover:text-indigo-500 font-medium">Add
                                                    your first item</button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="bg-slate-50 px-4 py-3 border-t border-slate-200 text-center sm:px-6">
                                <button type="button" @click="openProductPicker()"
                                    class="text-sm font-medium text-slate-600 hover:text-indigo-600">
                                    + Add Item
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Picker Modal -->
            <div x-show="productPicker.isOpen" class="relative z-50" aria-labelledby="modal-title" role="dialog"
                aria-modal="true" style="display: none;">
                <div x-show="productPicker.isOpen" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-500/75 transition-opacity"></div>

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

                            <!-- Modal Header -->
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-bold text-slate-900">Add Item to Template</h3>
                                <button type="button" @click="productPicker.isOpen = false"
                                    class="text-slate-400 hover:text-slate-500">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Search -->
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
                                            <!-- Product Image -->
                                            <div
                                                class="h-12 w-12 rounded-lg bg-slate-100 flex-shrink-0 overflow-hidden ring-1 ring-slate-200">
                                                <template x-if="product.images && product.images.length > 0">
                                                    <img :src="product.images[0].image_path.startsWith('http') ? product.images[0].image_path : '/storage/' + product.images[0].image_path"
                                                        class="h-full w-full object-cover">
                                                </template>
                                                <template x-if="!product.images || product.images.length === 0">
                                                    <div class="h-full w-full flex items-center justify-center">
                                                        <svg class="h-6 w-6 text-slate-300" fill="none"
                                                            viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                        </svg>
                                                    </div>
                                                </template>
                                            </div>
                                            <!-- Product Details -->
                                            <div>
                                                <div class="font-semibold text-slate-900" x-text="product.name"></div>
                                                <div class="text-xs text-slate-500" x-text="product.sku || 'No SKU'">
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Price -->
                                        <div class="text-right">
                                            <div class="font-bold text-indigo-600" x-text="product.unit_price"></div>
                                            <div class="text-[10px] text-slate-400"
                                                x-text="'Per ' + (product.unit_type || 'nos')"></div>
                                        </div>
                                    </button>
                                </template>
                                <div x-show="filteredProducts.length === 0"
                                    class="text-center py-4 text-slate-500 text-sm">
                                    No products found matching your search.
                                </div>
                            </div>

                            <!-- Custom Item -->
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

            <!-- Product Configuration Modal -->
            <div x-show="configModal.isOpen" class="relative z-[60]" aria-labelledby="modal-title" role="dialog"
                aria-modal="true" style="display: none;">
                <div x-show="configModal.isOpen" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-500/75 transition-opacity">
                </div>

                <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                    <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                        <div x-show="configModal.isOpen" x-transition:enter="ease-out duration-300"
                            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                            x-transition:leave="ease-in duration-200"
                            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                            @click.away="closeConfigModal()"
                            class="relative transform overflow-hidden rounded-xl bg-white px-4 pb-4 pt-5 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">

                            <div x-show="configModal.product">
                                <div class="flex items-center justify-between mb-6">
                                    <h3 class="text-lg font-bold text-slate-900"
                                        x-text="'Configure ' + (configModal.product?.name || 'Item')"></h3>
                                    <button type="button" @click="closeConfigModal()"
                                        class="text-slate-400 hover:text-slate-500">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>

                                <div class="space-y-6">
                                    <template x-for="option in configModal.product?.options" :key="option.id">
                                        <div>
                                            <label class="block text-sm font-medium leading-6 text-slate-900"
                                                x-text="option.name"></label>
                                            <div class="mt-2 grid grid-cols-2 gap-3 sm:grid-cols-3">
                                                <template x-for="value in option.values" :key="value.id">
                                                    <div @click="configModal.options[option.id] = value.id"
                                                        :class="{'ring-2 ring-indigo-600 border-transparent': configModal.options[option.id] === value.id, 'border-slate-300 hover:border-indigo-400': configModal.options[option.id] !== value.id}"
                                                        class="cursor-pointer flex items-center justify-center rounded-md border bg-white px-3 py-3 text-sm font-medium uppercase sm:flex-1 shadow-sm focus:outline-none relative">
                                                        <span x-text="value.value"></span>
                                                        <span x-show="value.price_adjustment != 0"
                                                            class="absolute top-0 right-0 -mt-1 -mr-1 text-[10px] text-indigo-600 bg-indigo-50 px-1 rounded-full"
                                                            x-text="(value.price_adjustment > 0 ? '+' : '') + value.price_adjustment">
                                                        </span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <div class="mt-8 border-t border-slate-100 pt-6 flex justify-end">
                                    <button type="button" @click="confirmConfig()"
                                        class="rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                        Add to Template
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        function templateForm(products, initialItems, unitTypes) {
            return {
                products: products,
                items: initialItems.length ? initialItems : [],
                unitTypes: unitTypes,

                productPicker: {
                    isOpen: false,
                    search: ''
                },

                configModal: {
                    isOpen: false,
                    product: null,
                    options: {},
                    basePrice: 0,
                    activeItem: null,
                    isNewItem: false // Track if we are configuring a new item or editing existing
                },

                init() {
                    // Normalize items
                    this.items.forEach(item => {
                        if (!item.item_name && item.name) {
                            item.item_name = item.name;
                        }
                        if (!item.options) {
                            item.options = [];
                        }
                        // Ensure unit_type_id is a string for reliable select matching
                        if (item.unit_type_id !== null && item.unit_type_id !== undefined && item.unit_type_id !== '') {
                            item.unit_type_id = String(item.unit_type_id);
                        } else {
                            item.unit_type_id = '';
                        }
                        item._showTypePicker = !!item.unit_type_id;
                    });
                },

                onUnitTypeChange(item) {
                    if (item.unit_type_id) {
                        const ut = this.unitTypes.find(u => u.id == item.unit_type_id);
                        if (ut && ut.units && ut.units.length > 0) {
                            item.unit_type = ut.units[0];
                        }
                    }
                },

                getAvailableUnits(typeId) {
                    const ut = this.unitTypes.find(u => u.id == typeId);
                    return ut ? ut.units : [];
                },

                get filteredProducts() {
                    if (this.productPicker.search === '') {
                        return this.products;
                    }
                    return this.products.filter(product => {
                        return product.name.toLowerCase().includes(this.productPicker.search.toLowerCase()) ||
                            (product.sku && product.sku.toLowerCase().includes(this.productPicker.search.toLowerCase()));
                    });
                },

                openProductPicker() {
                    this.productPicker.search = '';
                    this.productPicker.isOpen = true;
                },

                selectProduct(product) {
                    // Check if product has options
                    if (product.options && product.options.length > 0) {
                        // Close picker and open config modal
                        this.productPicker.isOpen = false;
                        this.openConfigModal(product, null, true); // true = isNewItem
                    } else {
                        // Directly add item
                        this.items.push({
                            product_id: product.id,
                            item_name: product.name,
                            description: product.description || '',
                            quantity: 1,
                            unit_type: product.unit_type || 'nos',
                            unit_type_id: product.unit_type_id || '',
                            unit_price: parseFloat(product.unit_price || 0),
                            _showTypePicker: false,
                            options: []
                        });
                        this.productPicker.isOpen = false;
                    }
                },

                addCustomItem() {
                    this.items.push({
                        product_id: null,
                        item_name: 'New Custom Item',
                        description: '',
                        quantity: 1,
                        unit_type: 'nos',
                        unit_type_id: '',
                        unit_price: 0,
                        _showTypePicker: false,
                        options: []
                    });
                    this.productPicker.isOpen = false;
                },

                openConfigModal(product, item = null, isNewItem = false) {
                    this.configModal.product = product;
                    this.configModal.activeItem = item;
                    this.configModal.isNewItem = isNewItem;
                    this.configModal.basePrice = parseFloat(product.unit_price || 0);
                    this.configModal.options = {};

                    // Pre-select first options
                    if (product.options) {
                        product.options.forEach(opt => {
                            if (opt.values && opt.values.length > 0) {
                                this.configModal.options[opt.id] = opt.values[0].id; // Default to first
                            }
                        });
                    }

                    this.configModal.isOpen = true;
                },

                closeConfigModal() {
                    this.configModal.isOpen = false;
                    this.configModal.product = null;
                    this.configModal.options = {};
                    this.configModal.activeItem = null;

                    // If we cancelled a new item, reopen product picker? 
                    // Let's just go back to picker if it was a new item selection flow
                    if (this.configModal.isNewItem) {
                        this.productPicker.isOpen = true;
                    }
                },

                confirmConfig() {
                    const product = this.configModal.product;

                    // Calculate Price & Options
                    let finalPrice = this.configModal.basePrice;
                    const selectedOptionsList = [];

                    if (product.options) {
                        product.options.forEach(opt => {
                            const selectedValueId = this.configModal.options[opt.id];
                            const val = opt.values.find(v => v.id == selectedValueId);
                            if (val) {
                                finalPrice += parseFloat(val.price_adjustment || 0);
                                selectedOptionsList.push({
                                    name: opt.name,
                                    value: val.value,
                                    price_adjustment: val.price_adjustment
                                });
                            }
                        });
                    }

                    if (this.configModal.isNewItem) {
                        // Create new item
                        this.items.push({
                            product_id: product.id,
                            item_name: product.name,
                            description: product.description || '',
                            quantity: 1,
                            unit_type: product.unit_type || 'nos',
                            unit_type_id: product.unit_type_id || '',
                            unit_price: finalPrice,
                            _showTypePicker: false,
                            options: selectedOptionsList
                        });
                    } else if (this.configModal.activeItem) {
                        // Update existing item
                        const item = this.configModal.activeItem;
                        item.unit_price = finalPrice;
                        item.options = selectedOptionsList;
                    }

                    this.configModal.isOpen = false;
                    this.configModal.product = null;
                    this.configModal.options = {};
                    this.configModal.activeItem = null;
                },

                getProductImage(productId) {
                    if (!productId) return null;
                    const product = this.products.find(p => p.id == productId);
                    if (product && product.images && product.images.length > 0) {
                        const path = product.images[0].image_path;
                        return path.startsWith('http') ? path : '/storage/' + path;
                    }
                    return null;
                }
            };
        }
    </script>
</x-app-layout>
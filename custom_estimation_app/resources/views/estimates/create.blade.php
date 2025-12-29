<x-app-layout>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

    <div class="sm:flex sm:items-center mb-8">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Create New Estimate</h1>
            <p class="mt-2 text-sm text-slate-500">Build a professional estimate manually or using templates.</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            <a href="{{ route('estimates.index') }}"
                class="block rounded-lg bg-white px-4 py-2.5 text-center text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition-all duration-200">
                Cancel & Back
            </a>
        </div>
    </div>

    <!-- Main Logic -->
    <div x-data="estimateBuilder({ 
            templates: {{ $templates->toJson() }}, 
            packages: {{ $packages->toJson() }},
            products: {{ $products->toJson() }},
            defaults: {{ json_encode($defaults) }}
         })" x-init="init()" class="pb-20">

        <form @submit.prevent="submitForm" class="space-y-8">

            <!-- Quick Actions Toolbar -->
            <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 flex flex-wrap gap-4 items-center justify-between"
                x-show="estimate.type === 'room_based'">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span class="text-sm font-semibold text-indigo-900">Quick Actions:</span>
                </div>
                <div class="flex gap-3">
                    <!-- Template Loader -->
                    <div class="relative" x-data="{ open: false }">
                        <button type="button" @click="open = !open" @click.away="open = false"
                            class="inline-flex items-center gap-x-1.5 rounded-md bg-white px-3 py-2 text-sm font-semibold text-indigo-600 shadow-sm ring-1 ring-inset ring-indigo-300 hover:bg-indigo-50">
                            <svg class="-ml-0.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path
                                    d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                            </svg>
                            Load Room Template
                            <svg class="-mr-1 h-5 w-5 text-indigo-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div x-show="open"
                            class="absolute right-0 z-10 mt-2 w-56 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                            style="display: none;">
                            <div class="py-1">
                                <template x-for="template in templates" :key="template.id">
                                    <button type="button" @click="applyTemplate(template); open = false"
                                        class="block w-full px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-100 hover:text-slate-900"
                                        x-text="template.name"></button>
                                </template>
                                <div x-show="templates.length === 0" class="px-4 py-2 text-sm text-slate-500">No
                                    templates available</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- General Information -->
            <div class="bg-white shadow-sm ring-1 ring-slate-900/5 sm:rounded-xl">
                <div class="px-4 py-6 sm:p-8">
                    <h2 class="text-base font-semibold leading-7 text-slate-900 mb-6">Estimate Details</h2>
                    <div class="grid max-w-4xl grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">

                        <div class="sm:col-span-4 select-client-container">
                            <label class="block text-sm font-medium leading-6 text-slate-900">Client / Lead *</label>
                            <div class="mt-2">
                                <select id="client-search" x-ref="clientSearch"
                                    class="mt-2 block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                                    <option value="">Search for a client or lead...</option>
                                </select>
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium leading-6 text-slate-900">Status</label>
                            <select x-model="estimate.status" required
                                class="mt-2 block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                                <option value="draft">Draft</option>
                                <option value="sent">Sent</option>
                                <option value="accepted">Accepted</option>
                                <option value="declined">Declined</option>
                                <option value="expired">Expired</option>
                            </select>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium leading-6 text-slate-900">PDF Theme</label>
                            <select x-model="estimate.pdf_theme" required
                                class="mt-2 block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                                <option value="modern">Modern (Default)</option>
                                <option value="classic">Classic</option>
                                <option value="minimal">Minimal</option>
                            </select>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium leading-6 text-slate-900">Estimate Date *</label>
                            <input type="date" x-model="estimate.estimate_date" required
                                class="mt-2 block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium leading-6 text-slate-900">Expiry Date</label>
                            <input type="date" x-model="estimate.expiry_date"
                                class="mt-2 block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium leading-6 text-slate-900">Currency</label>
                            <div class="mt-2.5 flex items-center gap-2">
                                <span
                                    class="inline-flex items-center rounded-md bg-slate-50 px-3 py-1.5 text-sm font-semibold text-slate-700 ring-1 ring-inset ring-slate-200"
                                    x-text="estimate.currency"></span>
                                <span class="text-xs text-slate-500">(System Default)</span>
                            </div>
                        </div>

                        <div class="sm:col-span-6">
                            <label class="block text-sm font-medium leading-6 text-slate-900 mb-3">Estimate Type
                                *</label>
                            <div class="flex gap-4">
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" x-model="estimate.type" value="room_based"
                                        class="h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-600">
                                    <span class="ml-2 text-sm text-slate-700">Room-Based (Recommended)</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" x-model="estimate.type" value="standard"
                                        class="h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-600">
                                    <span class="ml-2 text-sm text-slate-700">Standard List</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items/Sections Editor -->
            <div class="py-4">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold tracking-tight text-slate-900">
                        <span x-show="estimate.type === 'room_based'">Rooms & Items</span>
                        <span x-show="estimate.type === 'standard'">Line Items</span>
                    </h2>

                    <div class="flex gap-3">
                        <button type="button" @click="addSection" x-show="estimate.type === 'room_based'"
                            class="inline-flex items-center gap-x-1.5 rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                            <svg class="-ml-0.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path
                                    d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                            </svg>
                            Add Room
                        </button>
                        <button type="button" @click="openProductPicker(null)" x-show="estimate.type === 'standard'"
                            class="inline-flex items-center gap-x-1.5 rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                            <svg class="-ml-0.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path
                                    d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                            </svg>
                            Add Item
                        </button>
                    </div>
                </div>

                <!-- Room-Based List -->
                <div x-show="estimate.type === 'room_based'" class="space-y-6 sections-container">
                    <template x-for="(section, sectionIndex) in estimate.sections" :key="sectionIndex">
                        <div class="border border-slate-200 rounded-xl bg-white shadow-sm overflow-hidden">
                            <!-- Room Header -->
                            <div
                                class="bg-slate-50 border-b border-slate-200 px-4 py-3 sm:px-6 flex items-center justify-between gap-4">
                                <div class="flex items-center gap-3 flex-1">
                                    <div class="section-handle cursor-move text-slate-400 hover:text-slate-600">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 8h16M4 16h16" />
                                        </svg>
                                    </div>
                                    <input type="text" x-model="section.name" placeholder="Room Name (e.g. Living Area)"
                                        class="block w-full text-lg font-bold bg-transparent border-0 p-0 text-slate-900 focus:ring-0 placeholder:text-slate-400">
                                </div>
                                <div class="flex items-center gap-2">
                                    <!-- Package Loader for Section -->
                                    <div class="relative" x-data="{ pkgOpen: false }">
                                        <button type="button" @click="pkgOpen = !pkgOpen" @click.away="pkgOpen = false"
                                            class="text-xs font-semibold text-indigo-600 hover:text-indigo-900 flex items-center gap-1 bg-indigo-50 px-2 py-1 rounded">
                                            + Add Package
                                        </button>
                                        <div x-show="pkgOpen"
                                            class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                                            style="display: none;">
                                            <div class="py-1">
                                                <template x-for="pkg in packages" :key="pkg.id">
                                                    <button type="button"
                                                        @click="applyPackage(pkg, sectionIndex); pkgOpen = false"
                                                        class="block w-full px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-100"
                                                        x-text="pkg.name"></button>
                                                </template>
                                                <div x-show="packages.length === 0"
                                                    class="px-4 py-2 text-sm text-slate-500">No packages</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="h-4 w-px bg-slate-300 mx-1"></div>

                                    <button type="button" @click="openProductPicker(sectionIndex)"
                                        class="text-sm font-medium text-slate-600 hover:text-indigo-600">
                                        + Add Item
                                    </button>
                                    <button type="button" @click="removeSection(sectionIndex)"
                                        class="text-sm font-medium text-rose-600 hover:text-rose-900 ml-2">
                                        Remove Room
                                    </button>
                                </div>
                            </div>

                            <!-- Items Table -->
                            <div class="overflow-x-auto min-h-[100px]">
                                <table class="min-w-full divide-y divide-slate-200">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th scope="col" class="w-10 py-3 px-3"></th>
                                            <th scope="col"
                                                class="px-3 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider w-16">
                                                Image
                                            </th>
                                            <th scope="col"
                                                class="px-3 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                                Item</th>
                                            <th scope="col"
                                                class="px-3 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider w-24">
                                                Size</th>
                                            <th scope="col"
                                                class="px-3 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider w-24">
                                                Price</th>
                                            <th scope="col"
                                                class="px-3 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wider w-20">
                                                Qty</th>
                                            <th scope="col"
                                                class="px-3 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wider w-16">
                                                Unit</th>
                                            <th scope="col"
                                                class="px-3 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider w-20">
                                                Tax 1 (%)</th>
                                            <th scope="col"
                                                class="px-3 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider w-20">
                                                Tax 2 (%)</th>
                                            <th scope="col"
                                                class="px-3 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider w-24">
                                                Total</th>
                                            <th scope="col" class="relative px-3 py-3 w-10"><span
                                                    class="sr-only">Actions</span></th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-slate-200 section-items-sortable">
                                        <template x-for="(item, itemIndex) in section.items" :key="itemIndex">
                                            <tr class="group hover:bg-slate-50 transition-colors">
                                                <td class="px-3 py-2 text-center text-slate-400 cursor-move handle">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M4 8h16M4 16h16" />
                                                    </svg>
                                                </td>
                                                <td class="px-3 py-2 text-center">
                                                    <template x-if="item.image_url">
                                                        <img :src="item.image_url"
                                                            class="h-10 w-10 object-cover rounded-md mx-auto ring-1 ring-slate-200">
                                                    </template>
                                                    <template x-if="!item.image_url">
                                                        <div
                                                            class="h-10 w-10 bg-slate-100 rounded-md mx-auto flex items-center justify-center ring-1 ring-slate-200">
                                                            <svg class="h-5 w-5 text-slate-300" fill="none"
                                                                viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                            </svg>
                                                        </div>
                                                    </template>
                                                </td>
                                                <td class="px-3 py-2">
                                                    <input type="text" x-model="item.name" placeholder="Item Name"
                                                        class="block w-full border-0 p-0 text-sm font-medium text-slate-900 focus:ring-0 placeholder:text-slate-400 bg-transparent">
                                                    <input type="text" x-model="item.description"
                                                        placeholder="Description"
                                                        class="block w-full border-0 p-0 text-xs text-slate-500 focus:ring-0 placeholder:text-slate-400 bg-transparent mt-1">
                                                </td>
                                                <td class="px-3 py-2">
                                                    <input type="text" x-model="item.size" placeholder="Size"
                                                        class="block w-full border-0 p-0 text-sm text-slate-900 focus:ring-0 bg-transparent placeholder:text-slate-400"
                                                        x-show="!item.showCalculator">

                                                    <div x-show="item.showCalculator" class="flex items-center gap-1"
                                                        style="display: none;">
                                                        <div class="flex flex-col">
                                                            <input type="number" step="0.01" x-model="item.length"
                                                                placeholder="L" @input="calculateQuantity(item)"
                                                                class="block w-12 border-0 p-0 text-xs text-slate-900 focus:ring-0 bg-slate-50 rounded text-center mb-0.5">
                                                            <input type="number" step="0.01" x-model="item.width"
                                                                placeholder="W" @input="calculateQuantity(item)"
                                                                class="block w-12 border-0 p-0 text-xs text-slate-900 focus:ring-0 bg-slate-50 rounded text-center">
                                                        </div>
                                                        <span class="text-xs text-slate-400" x-text="(item.length && item.width) ? (item.length * item.width).toFixed(2) : '='"></span>
                                                    </div>
                                                </td>
                                                <td class="px-3 py-2">
                                                    <input type="number" step="0.01" x-model="item.unit_price"
                                                        @input="calculateTotals"
                                                        class="block w-full border-0 p-0 text-sm text-slate-900 focus:ring-0 bg-transparent text-right">
                                                </td>
                                                <td class="px-3 py-2">
                                                    <div class="flex items-center gap-1">
                                                        <input type="number" step="0.01" x-model="item.quantity"
                                                            @input="calculateTotals"
                                                            class="block w-full border-0 p-0 text-sm text-slate-900 focus:ring-0 bg-transparent text-center"
                                                            :readonly="item.showCalculator"
                                                            :class="{'bg-slate-50': item.showCalculator}">
                                                        <button type="button" @click="toggleCalculator(item)"
                                                            class="text-slate-400 hover:text-indigo-600 transition-colors p-1"
                                                            :class="{'text-indigo-600 bg-indigo-50 rounded': item.showCalculator}"
                                                            title="Toggle Calculator">
                                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                                stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </td>
                                                <td class="px-3 py-2">
                                                    <input type="text" x-model="item.unit_type"
                                                        class="block w-full border-0 p-0 text-xs text-slate-500 focus:ring-0 bg-transparent text-center">
                                                </td>
                                                <td class="px-3 py-2">
                                                    <input type="number" step="0.01" x-model="item.tax_1"
                                                        @input="calculateTotals"
                                                        class="block w-full border-0 p-0 text-sm text-slate-900 focus:ring-0 bg-transparent text-right">
                                                </td>
                                                <td class="px-3 py-2">
                                                    <input type="number" step="0.01" x-model="item.tax_2"
                                                        @input="calculateTotals"
                                                        class="block w-full border-0 p-0 text-sm text-slate-900 focus:ring-0 bg-transparent text-right">
                                                </td>
                                                <td class="px-3 py-2 text-right text-sm font-medium text-slate-900">
                                                    <span x-text="calculateItemTotal(item).toFixed(2)"></span>
                                                </td>
                                                <td class="px-3 py-2 text-right">
                                                    <button type="button" @click="removeItem(sectionIndex, itemIndex)"
                                                        class="text-slate-400 hover:text-rose-600 transition-colors">
                                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd"
                                                                d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <div x-show="section.items.length === 0"
                                class="text-center py-6 border-2 border-dashed border-slate-200 rounded-lg">
                                <p class="text-sm text-slate-500">No items in this room.</p>
                                <button type="button" @click="openProductPicker(sectionIndex)"
                                    class="mt-2 text-sm font-medium text-indigo-600 hover:text-indigo-500">Add first
                                    item</button>
                            </div>
                        </div>

                        <!-- Room Footer -->
                        <div class="bg-slate-50 border-t border-slate-200 px-4 py-3 sm:px-6 flex justify-end">
                            <div class="text-sm text-slate-600">
                                Room Total: <span class="font-bold text-slate-900"
                                    x-text="calculateSectionTotal(section).toFixed(2)"></span>
                            </div>
                        </div>

                    </template>

                    <button type="button" @click="addSection"
                        class="w-full py-4 border-2 border-dashed border-slate-300 rounded-xl text-slate-500 hover:border-indigo-500 hover:text-indigo-600 hover:bg-indigo-50 transition-all font-medium flex items-center justify-center gap-2">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path
                                d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                        </svg>
                        Add New Room
                    </button>
                </div>

                <!-- Standard List Table -->
                <div x-show="estimate.type === 'standard'" class="overflow-x-auto min-h-[100px]">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="w-10 py-3 px-3"></th>
                                <th scope="col"
                                    class="px-3 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider w-16">
                                    Image
                                </th>
                                <th scope="col"
                                    class="px-3 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">
                                    Item</th>
                                <th scope="col"
                                    class="px-3 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider w-24">
                                    Size</th>
                                <th scope="col"
                                    class="px-3 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider w-24">
                                    Price</th>
                                <th scope="col"
                                    class="px-3 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wider w-20">
                                    Qty</th>
                                <th scope="col"
                                    class="px-3 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wider w-16">
                                    Unit</th>
                                <th scope="col"
                                    class="px-3 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider w-20">
                                    Tax 1 (%)</th>
                                <th scope="col"
                                    class="px-3 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider w-20">
                                    Tax 2 (%)</th>
                                <th scope="col"
                                    class="px-3 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider w-24">
                                    Total</th>
                                <th scope="col" class="relative px-3 py-3 w-10"><span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200 standard-items-sortable">
                            <template x-for="(item, itemIndex) in estimate.items" :key="itemIndex">
                                <tr class="group hover:bg-slate-50 transition-colors">
                                    <td class="px-3 py-2 text-center text-slate-400 cursor-move handle">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 8h16M4 16h16" />
                                        </svg>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <template x-if="item.image_url">
                                            <img :src="item.image_url"
                                                class="h-10 w-10 object-cover rounded-md mx-auto ring-1 ring-slate-200">
                                        </template>
                                        <template x-if="!item.image_url">
                                            <div
                                                class="h-10 w-10 bg-slate-100 rounded-md mx-auto flex items-center justify-center ring-1 ring-slate-200">
                                                <svg class="h-5 w-5 text-slate-300" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                        </template>
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="text" x-model="item.name" placeholder="Item Name"
                                            class="block w-full border-0 p-0 text-sm font-medium text-slate-900 focus:ring-0 placeholder:text-slate-400 bg-transparent">
                                        <input type="text" x-model="item.description" placeholder="Description"
                                            class="block w-full border-0 p-0 text-xs text-slate-500 focus:ring-0 placeholder:text-slate-400 bg-transparent mt-1">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="text" x-model="item.size" placeholder="Size"
                                            class="block w-full border-0 p-0 text-sm text-slate-900 focus:ring-0 bg-transparent placeholder:text-slate-400"
                                            x-show="!item.showCalculator">

                                        <div x-show="item.showCalculator" class="flex items-center gap-1"
                                            style="display: none;">
                                            <div class="flex flex-col">
                                                <input type="number" step="0.01" x-model="item.length" placeholder="L"
                                                    @input="calculateQuantity(item)"
                                                    class="block w-12 border-0 p-0 text-xs text-slate-900 focus:ring-0 bg-slate-50 rounded text-center mb-0.5">
                                                <input type="number" step="0.01" x-model="item.width" placeholder="W"
                                                    @input="calculateQuantity(item)"
                                                    class="block w-12 border-0 p-0 text-xs text-slate-900 focus:ring-0 bg-slate-50 rounded text-center">
                                            </div>
                                            <span class="text-xs text-slate-400">=</span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" step="0.01" x-model="item.unit_price"
                                            @input="calculateTotals"
                                            class="block w-full border-0 p-0 text-sm text-slate-900 focus:ring-0 bg-transparent text-right">
                                    </td>
                                    <td class="px-3 py-2">
                                        <div class="flex items-center gap-1">
                                            <input type="number" step="0.01" x-model="item.quantity"
                                                @input="calculateTotals"
                                                class="block w-full border-0 p-0 text-sm text-slate-900 focus:ring-0 bg-transparent text-center"
                                                :readonly="item.showCalculator"
                                                :class="{'bg-slate-50': item.showCalculator}">
                                            <button type="button" @click="toggleCalculator(item)"
                                                class="text-slate-400 hover:text-indigo-600 transition-colors p-1"
                                                :class="{'text-indigo-600 bg-indigo-50 rounded': item.showCalculator}"
                                                title="Toggle Calculator">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="text" x-model="item.unit_type"
                                            class="block w-full border-0 p-0 text-xs text-slate-500 focus:ring-0 bg-transparent text-center">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" step="0.01" x-model="item.tax_1" @input="calculateTotals"
                                            class="block w-full border-0 p-0 text-sm text-slate-900 focus:ring-0 bg-transparent text-right">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" step="0.01" x-model="item.tax_2" @input="calculateTotals"
                                            class="block w-full border-0 p-0 text-sm text-slate-900 focus:ring-0 bg-transparent text-right">
                                    </td>
                                    <td class="px-3 py-2 text-right text-sm font-medium text-slate-900">
                                        <span x-text="calculateItemTotal(item).toFixed(2)"></span>
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <button type="button" @click="removeItem(null, itemIndex)"
                                            class="text-slate-400 hover:text-rose-600 transition-colors">
                                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <button type="button" @click="openProductPicker(null)"
                    class="h-full min-h-[160px] border-2 border-dashed border-slate-300 rounded-xl text-slate-400 hover:border-indigo-500 hover:text-indigo-600 hover:bg-indigo-50 transition-all font-medium flex flex-col items-center justify-center gap-2">
                    <svg class="h-8 w-8" viewBox="0 0 20 20" fill="currentColor">
                        <path
                            d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                    </svg>
                    <span>Add Item</span>
                </button>
            </div>

            <!-- Product Picker Modal -->
            <div x-show="productPicker.isOpen" class="relative z-50" aria-labelledby="modal-title" role="dialog"
                aria-modal="true" style="display: none;">
                <div x-show="productPicker.isOpen" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-500/75 transition-opacity">
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
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
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
                                                        <img :src="'/storage/' + product.images[0].image_path"
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
                                                <div>
                                                    <div class="font-semibold text-slate-900" x-text="product.name">
                                                    </div>
                                                    <div class="text-xs text-slate-500"
                                                        x-text="product.sku || 'No SKU'">
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

                                    <div x-show="filteredProducts.length === 0"
                                        class="text-center py-4 text-slate-500 text-sm">
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

            <!-- Financials Footer -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Notes -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-slate-500 mb-1">Client
                            Note</label>
                        <textarea x-model="estimate.client_note" rows="3"
                            class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                            placeholder="Message visible to client..."></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-slate-500 mb-1">Terms &
                            Conditions</label>
                        <textarea x-model="estimate.terms" rows="3"
                            class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                            placeholder="Payment terms, validity..."></textarea>
                    </div>
                </div>

                <!-- Totals -->
                <div class="bg-slate-50 rounded-xl p-6 h-fit">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-slate-600">Subtotal</dt>
                            <dd class="font-medium text-slate-900" x-text="totals.subtotal.toFixed(2)"></dd>
                        </div>
                        <div class="flex justify-between" x-show="totals.totalTax > 0">
                            <dt class="text-slate-600">Tax</dt>
                            <dd class="font-medium text-slate-900" x-text="totals.totalTax.toFixed(2)"></dd>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <dt class="text-slate-600">Discount</dt>
                            <dd class="flex items-center gap-2">
                                <select x-model="estimate.discount_type" @change="calculateTotals"
                                    class="text-xs border-0 bg-transparent py-0 pl-0 pr-7 text-slate-500 focus:ring-0">
                                    <option value="percentage">%</option>
                                    <option value="fixed">Fixed</option>
                                </select>
                                <input type="number" x-model="estimate.discount_value" @input="calculateTotals"
                                    class="block w-20 rounded-md border-0 py-1 text-right text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 sm:text-sm sm:leading-6">
                            </dd>
                        </div>
                        <div class="flex justify-between border-t border-slate-200 pt-3">
                            <dt class="text-base font-bold text-slate-900">Grand Total</dt>
                            <dd class="text-base font-bold text-indigo-600"
                                x-text="estimate.currency + ' ' + totals.grandTotal.toFixed(2)"></dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Sticky Save Bar -->
            <div
                class="fixed bottom-0 left-0 right-0 bg-white border-t border-slate-200 p-4 sm:px-8 z-50 flex justify-end gap-3 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
                <button type="button" @click="previewPdf"
                    class="inline-flex items-center gap-x-1.5 rounded-md bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
                    <svg class="-ml-0.5 h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    Preview PDF
                </button>
                <button type="button" @click="window.history.back()"
                    class="text-sm font-semibold leading-6 text-slate-900 px-4">Cancel</button>
                <button type="submit"
                    class="rounded-md bg-indigo-600 px-8 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    Save Estimate
                </button>
            </div>
        </form>
    </div>

    <!-- Build Logic -->
    <script>
        function estimateBuilder(config = {}) {
            return {
                templates: config.templates || [],
                packages: config.packages || [],
                products: config.products || [],
                defaults: config.defaults || {},
                estimate: {
                    title: '',
                    client_id: '',
                    estimate_date: new Date().toISOString().split('T')[0],
                    expiry_date: '',
                    currency: config.defaults?.currency || 'USD',
                    type: 'room_based', // Default
                    discount_type: 'percentage',
                    discount_value: 0,
                    client_note: config.defaults?.client_note || '',
                    admin_note: '',
                    terms: config.defaults?.terms || '',
                    status: 'draft', // New default status
                    pdf_theme: 'modern', // New default PDF theme
                    sections: [
                        { name: 'Room 1', items: [] } // Start with 1 room
                    ],
                    items: []
                },
                totals: {
                    subtotal: 0,
                    totalTax: 0,
                    discount: 0,
                    grandTotal: 0
                },
                productPicker: {
                    isOpen: false,
                    search: '',
                    sectionIndex: null
                },

                get filteredProducts() {
                    if (!this.products) return [];
                    const productsArr = Array.isArray(this.products) ? this.products : Object.values(this.products);
                    const q = this.productPicker.search.toLowerCase();
                    return productsArr.filter(p =>
                        (p.name && p.name.toLowerCase().includes(q)) ||
                        (p.sku && p.sku.toLowerCase().includes(q))
                    );
                },

                init() {
                    // Logic to load existing estimate if editing will go here later, 
                    // for now defaults are fine for create.
                    this.calculateTotals();
                    this.$nextTick(() => {
                        this.initSortable();
                        this.initClientSearch();
                    });
                },

                initClientSearch() {
                    const el = this.$refs.clientSearch;

                    // Prevent double initialization
                    if (el._choices) return;

                    const choices = new Choices(el, {
                        searchEnabled: true,
                        searchPlaceholderValue: 'Type to search Perfex CRM...',
                        noResultsText: 'No clients found',
                        itemSelectText: '',
                    });

                    // Save instance to element to check later
                    el._choices = choices;

                    el.addEventListener('search', (event) => {
                        const query = event.detail.value;
                        if (query.length < 3) {
                            choices.setChoices([{ value: '', label: 'Type to search Perfex CRM...', disabled: true }], 'value', 'label', true);
                            return;
                        }

                        fetch(`{{ route('perfex.search') }}?q=${query}`)
                            .then(response => response.json())
                            .then(data => {
                                // Map data to Choices.js format: [{ value: 'id', label: 'name' }]
                                const formattedData = data.map(client => ({
                                    value: client.id,
                                    label: client.name + (client.email ? ` (${client.email})` : '')
                                }));
                                choices.setChoices(formattedData, 'value', 'label', true);
                            })
                            .catch(error => {
                                console.error('Error fetching clients:', error);
                                choices.setChoices([{ value: '', label: 'Error loading clients', disabled: true }], 'value', 'label', true);
                            });
                    });

                    el.addEventListener('change', (event) => {
                        this.estimate.client_id = event.detail.value;
                    });
                },

                initSortable() {
                    // Initialize Sortable for sections (Rooms)
                    const sectionsContainer = this.$el.querySelector('.sections-container');
                    if (sectionsContainer) {
                        new Sortable(sectionsContainer, {
                            animation: 150,
                            handle: '.section-handle',
                            onEnd: (evt) => {
                                const movedItem = this.estimate.sections.splice(evt.oldIndex, 1)[0];
                                this.estimate.sections.splice(evt.newIndex, 0, movedItem);
                            }
                        });
                    }

                    // Room-Based Items
                    this.$el.querySelectorAll('.section-items-sortable').forEach(el => {
                        new Sortable(el, {
                            animation: 150,
                            group: 'items',
                            handle: '.handle',
                            onEnd: (evt) => {
                                const sectionIndex = Array.from(sectionsContainer.children).indexOf(evt.from.closest('.border.border-slate-200'));
                                if (sectionIndex !== -1) {
                                    const movedItem = this.estimate.sections[sectionIndex].items.splice(evt.oldIndex, 1)[0];
                                    this.estimate.sections[sectionIndex].items.splice(evt.newIndex, 0, movedItem);
                                }
                            }
                        });
                    });

                    // Standard List Items
                    const standardList = this.$el.querySelector('.standard-items-sortable');
                    if (standardList) {
                        new Sortable(standardList, {
                            animation: 150,
                            handle: '.handle',
                            onEnd: (evt) => {
                                const movedItem = this.estimate.items.splice(evt.oldIndex, 1)[0];
                                this.estimate.items.splice(evt.newIndex, 0, movedItem);
                            }
                        });
                    }
                },

                // --- Core Actions ---
                openProductPicker(sectionIndex) {
                    this.productPicker.sectionIndex = sectionIndex;
                    this.productPicker.search = '';
                    this.productPicker.isOpen = true;
                },

                selectProduct(product) {
                    const isFormula = product.calculation_method === 'formula';

                    // Format dimensions string for standard items
                    let sizeString = '';
                    if (product.dimensions) {
                        const dims = product.dimensions;
                        if (dims.length && dims.width) {
                            sizeString = `${dims.length} x ${dims.width}`;
                            if (dims.unit) sizeString += ` ${dims.unit}`;
                            if (dims.height) sizeString += ` x ${dims.height}`;
                        }
                    }

                    const newItem = {
                        name: product.name,
                        product_id: product.id,
                        unit_price: parseFloat(product.unit_price || 0),
                        quantity: 1,
                        size: sizeString,
                        unit_type: product.unit_type || 'nos',
                        description: product.description || '',
                        tax_1: parseFloat(this.defaults.tax_1_rate || 0),
                        tax_2: parseFloat(this.defaults.tax_2_rate || 0),
                        image_url: (product.images && product.images.length > 0) ? '/storage/' + product.images[0].image_path : null,
                        length: '',
                        width: '',
                        formula: isFormula ? 'area' : '',
                        showCalculator: isFormula
                    };
                    this.pushItem(newItem);
                    this.productPicker.isOpen = false;
                    this.calculateTotals();
                },

                addCustomItem() {
                    const newItem = {
                        name: '',
                        unit_price: 0,
                        quantity: 1,
                        size: '',
                        unit_type: 'nos',
                        tax_1: parseFloat(this.defaults.tax_1_rate || 0),
                        tax_2: parseFloat(this.defaults.tax_2_rate || 0),
                        length: '',
                        width: '',
                        formula: '',
                        showCalculator: false
                    };
                    this.pushItem(newItem);
                    this.productPicker.isOpen = false;
                },

                pushItem(item) {
                    if (this.productPicker.sectionIndex !== null) {
                        this.estimate.sections[this.productPicker.sectionIndex].items.push(item);
                    } else {
                        this.estimate.items.push(item);
                    }
                },

                addItem(sectionIndex) {
                    // Deprecated or can be kept for simple adds
                    this.openProductPicker(sectionIndex);
                },
                addSection() {
                    const count = this.estimate.sections.length + 1;
                    this.estimate.sections.push({ name: 'Room ' + count, items: [] });
                },
                removeSection(index) {
                    if (this.estimate.sections.length > 1) {
                        if (confirm('Are you sure you want to remove this room?')) {
                            this.estimate.sections.splice(index, 1);
                            this.calculateTotals();
                        }
                    } else {
                        alert('You must have at least one room.');
                    }
                },
                removeItem(sectionIndex, itemIndex) {
                    if (sectionIndex !== null) {
                        this.estimate.sections[sectionIndex].items.splice(itemIndex, 1);
                    } else {
                        this.estimate.items.splice(itemIndex, 1);
                    }
                    this.calculateTotals();
                },

                // --- Importers ---
                applyTemplate(template) {
                    // Create a new section from template
                    const newSection = {
                        name: template.name,
                        items: []
                    };

                    if (Array.isArray(template.items)) {
                        newSection.items = template.items.map(i => ({
                            name: i.item_name,
                            unit_price: parseFloat(i.unit_price || 0),
                            quantity: parseFloat(i.quantity || 1),
                            size: i.size || '',
                            unit_type: i.unit_type || 'nos',
                            description: '', // Template items currently don't have desc in JSON schema
                            tax_1: 0,
                            tax_2: 0
                        }));
                    }

                    this.estimate.sections.push(newSection);
                    this.calculateTotals();
                },

                applyPackage(pkg, sectionIndex) {
                    if (!pkg.items || !Array.isArray(pkg.items)) return;

                    const newItems = pkg.items.map(i => ({
                        name: i.item_name,
                        unit_price: parseFloat(i.unit_price || 0),
                        quantity: parseFloat(i.quantity || 1),
                        size: i.size || '',
                        unit_type: i.unit_type || 'nos',
                        description: `Package: ${pkg.name}`,
                        tax_1: 0,
                        tax_2: 0
                    }));

                    if (sectionIndex !== null) {
                        this.estimate.sections[sectionIndex].items.push(...newItems);
                    } else {
                        // Standard mode integration if needed, though button is typically in room mode
                        this.estimate.items.push(...newItems);
                    }
                    this.calculateTotals();
                },

                // --- Calculations ---
                calculateItemTotal(item) {
                    return (parseFloat(item.unit_price) || 0) * (parseFloat(item.quantity) || 0);
                },

                calculateSectionTotal(section) {
                    return section.items.reduce((sum, item) => sum + this.calculateItemTotal(item), 0);
                },

                calculateTotals() {
                    let subtotal = 0;
                    const allItems = this.estimate.type === 'room_based'
                        ? this.estimate.sections.flatMap(s => s.items)
                        : this.estimate.items;

                    allItems.forEach(item => {
                        subtotal += this.calculateItemTotal(item);
                    });

                    let discount = 0;
                    if (this.estimate.discount_value > 0) {
                        discount = this.estimate.discount_type === 'percentage'
                            ? subtotal * (this.estimate.discount_value / 100)
                            : parseFloat(this.estimate.discount_value);
                    }

                    // Simple Tax Logic (could be complex per item, currently ignored in grand total for simplicity unless we sum item taxes)
                    // For now, let's assume item prices are tax inclusive or tax is 0 for MVP simple calculation
                    // But wait, the item UI has tax inputs.
                    // Correct logic:
                    let totalTax = 0;
                    allItems.forEach(item => {
                        const itemTotal = this.calculateItemTotal(item); // this is pre-tax
                        // Tax logic in previous file was: (subtotal * tax1/100)
                        const t1 = itemTotal * ((parseFloat(item.tax_1) || 0) / 100);
                        const t2 = itemTotal * ((parseFloat(item.tax_2) || 0) / 100);
                        totalTax += (t1 + t2);
                    });

                    this.totals = {
                        subtotal,
                        totalTax,
                        discount,
                        grandTotal: subtotal + totalTax - discount
                    };
                },

                toggleCalculator(item) {
                    item.showCalculator = !item.showCalculator;
                    // Auto-focus logic could be added here if needed
                },

                calculateQuantity(item) {
                    calculateQuantity(item) {
                        // Validation: Prevent negative input
                        if (item.length < 0) item.length = 0;
                        if (item.width < 0) item.width = 0;

                        if (item.length && item.width) {
                            const l = parseFloat(item.length) || 0;
                            const w = parseFloat(item.width) || 0;
                            if (l >= 0 && w >= 0) {
                                item.quantity = (l * w).toFixed(2);
                                item.formula = 'area';

                                // Visual feedback for area could be stored in a separate property if needed for UI, 
                                // but here we are primarily updating quantity.
                                // If we want to show '100 sq.ft' in the UI, we can access item.quantity directly.

                                this.calculateTotals();
                            }
                        } else {
                            // Reset if cleared? Or keep last valid? 
                            // Let's keep existing behavior but maybe zero out if both empty?
                            // item.quantity = 0; // Optional
                        }
                    },
                },

                previewPdf() {
                    // Logic to open a preview window with the current state
                    // We can't really "preview" a POST form easily without saving, 
                    // unless we use a temporary preview route that accepts the whole state via POST
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route("estimates.preview") }}';
                    form.target = '_blank';
                    form.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}">';

                    const app = (k, v) => { const i = document.createElement('input'); i.type = 'hidden'; i.name = k; i.value = v; form.appendChild(i); };
                    const fields = ['title', 'client_id', 'estimate_date', 'expiry_date', 'currency', 'type', 'discount_type', 'discount_value', 'client_note', 'admin_note', 'terms', 'status', 'pdf_theme'];
                    fields.forEach(f => app(f, this.estimate[f] || ''));

                    if (this.estimate.type === 'room_based') {
                        this.estimate.sections.forEach((s, sIdx) => {
                            app(`sections[${sIdx}][name]`, s.name);
                            s.items.forEach((i, iIdx) => {
                                for (const [k, v] of Object.entries(i)) {
                                    app(`sections[${sIdx}][items][${iIdx}][${k}]`, v);
                                }
                            });
                        });
                    } else {
                        this.estimate.items.forEach((i, iIdx) => {
                            for (const [k, v] of Object.entries(i)) {
                                app(`items[${iIdx}][${k}]`, v);
                            }
                        });
                    }
                    document.body.appendChild(form);
                    form.submit();
                    document.body.removeChild(form);
                },

                submitForm() {
                    // Form Submission Logic (Hidden Inputs)
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route("estimates.store") }}';
                    form.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}">';

                    // Helper to append
                    const app = (k, v) => {
                        const i = document.createElement('input');
                        i.type = 'hidden'; i.name = k; i.value = v;
                        form.appendChild(i);
                    };

                    // Basic Fields
                    for (const [key, value] of Object.entries(this.estimate)) {
                        if (!['sections', 'items'].includes(key)) app(key, value);
                    }

                    // Sections/Items
                    if (this.estimate.type === 'room_based') {
                        this.estimate.sections.forEach((s, sIdx) => {
                            app(`sections[${sIdx}][name]`, s.name);
                            s.items.forEach((i, iIdx) => {
                                for (const [k, v] of Object.entries(i)) app(`sections[${sIdx}][items][${iIdx}][${k}]`, v);
                            });
                        });
                    } else {
                        this.estimate.items.forEach((i, iIdx) => {
                            for (const [k, v] of Object.entries(i)) app(`items[${iIdx}][${k}]`, v);
                        });
                    }

                    document.body.appendChild(form);
                    form.submit();
                }
            };
        }
    </script>
</x-app-layout>
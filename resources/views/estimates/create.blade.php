<x-app-layout>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

    <div class="sm:flex sm:items-center mb-8">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Create New Estimate</h1>
            <p class="mt-2 text-sm text-slate-500">Create a new estimate with rooms and items.</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            <a href="{{ route('estimates.index') }}"
                class="block rounded-lg bg-white px-4 py-2.5 text-center text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition-all duration-200">
                Cancel
            </a>
        </div>
    </div>

    <!-- Main Logic -->
    <script>
        window.estimateData = {
            id: null,
            client_id: '',
            status: 'draft',
            title: '',
            estimate_date: new Date().toISOString().split('T')[0],
            expiry_date: '',
            currency: '{{ $defaults['currency'] ?? 'USD' }}',
            type: 'room_based',
            discount_type: 'percentage',
            discount_value: 0,
            client_note: {!! json_encode($defaults['client_note'] ?? '') !!},
            terms: {!! json_encode($defaults['terms'] ?? '') !!},
            pdf_template_id: '',
            sections: [],
            items: []
        };
        // Pass defaults to JS for tax logic if needed, though simpler to handle via Alpine data
    </script>
    <div x-data="estimateBuilder(window.estimateData)" x-init="init()" class="pb-20">

        <form action="{{ route('estimates.store') }}" method="POST" @submit.prevent="submitForm" class="space-y-8">
            @csrf

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
            <x-card padding="8">
                <h2 class="text-base font-semibold leading-7 text-slate-900 mb-6">Estimate Details</h2>
                <div class="grid max-w-4xl grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">

                    <div class="sm:col-span-4 select-client-container">
                        <x-input-label value="Client / Lead" required />
                        <div class="mt-2 text-slate-900">
                            <!-- Searchable Select populated via JS Choices or similar -->
                            <select id="client-search" x-ref="clientSearch" name="client_id" required
                                class="mt-2 block w-full rounded-lg border-slate-300 py-1.5 text-slate-900 shadow-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                                <option value="">Select a client...</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}">{{ $client->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <x-input-label value="Status" required />
                        <select x-model="estimate.status" name="status" required
                            class="mt-2 block w-full rounded-lg border-slate-300 py-1.5 text-slate-900 shadow-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                            <option value="draft">Draft</option>
                            <option value="sent">Sent</option>
                            <option value="accepted">Accepted</option>
                            <option value="declined">Declined</option>
                            <option value="expired">Expired</option>
                        </select>
                    </div>



                    <div class="sm:col-span-2">
                        <x-input-label value="PDF Template" required />
                        <select name="pdf_template_id" x-model="estimate.pdf_template_id"
                            class="mt-2 block w-full rounded-lg border-slate-300 py-1.5 text-slate-900 shadow-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                            <option value="">Select a template...</option>
                            @foreach($pdfTemplates as $template)
                                <option value="{{ $template->id }}" {{ $template->is_default ? 'selected' : '' }}>
                                    {{ $template->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <x-input-label value="Estimate Date" required />
                        <x-text-input type="date" x-model="estimate.estimate_date" name="estimate_date" required />
                    </div>

                    <div class="sm:col-span-2">
                        <x-input-label value="Expiry Date" />
                        <x-text-input type="date" x-model="estimate.expiry_date" name="expiry_date" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-input-label value="Currency" />
                        <div class="mt-2.5 flex items-center gap-2">
                            <span
                                class="inline-flex items-center rounded-md bg-slate-50 px-3 py-1.5 text-sm font-semibold text-slate-700 ring-1 ring-inset ring-slate-200"
                                x-text="estimate.currency"></span>
                            <input type="hidden" name="currency" x-model="estimate.currency">
                            <span class="text-xs text-slate-400 font-medium">(System Default)</span>
                        </div>
                    </div>

                    <div class="sm:col-span-6">
                        <x-input-label value="Estimate Type" class="mb-3" />
                        <!-- Type Selection (Radio or Toggle) -->
                        <div class="flex items-center space-x-4 mt-2">
                            <label class="flex items-center">
                                <input type="radio" name="type" value="room_based" x-model="estimate.type"
                                    class="h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-600">
                                <span class="ml-2 block text-sm font-medium text-slate-700">Room-Based</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="type" value="standard" x-model="estimate.type"
                                    class="h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-600">
                                <span class="ml-2 block text-sm font-medium text-slate-700">Standard List</span>
                            </label>
                        </div>
                    </div>
                </div>
            </x-card>

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
                                                class="px-3 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wider w-28">
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
                                                    <template x-if="item.options && item.options.length > 0">
                                                        <div class="flex flex-wrap gap-1 mt-1">
                                                            <template x-for="opt in item.options">
                                                                <span
                                                                    class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10"
                                                                    x-text="opt.name + ': ' + opt.value"></span>
                                                            </template>
                                                        </div>
                                                    </template>
                                                </td>
                                                <td class="px-3 py-2">
                                                    <input type="text" x-model="item.size" placeholder="Size"
                                                        class="block w-full border-0 p-0 text-sm text-slate-900 focus:ring-0 bg-transparent placeholder:text-slate-400"
                                                        x-show="!item.showCalculator">

                                                    <div x-show="item.showCalculator" class="flex items-center gap-1"
                                                        style="display: none;">
                                                        <div class="flex flex-col">
                                                            <div class="flex items-center gap-1 mb-0.5">
                                                                <input type="number" step="0.01" x-model="item.length"
                                                                    placeholder="L" @input="calculateQuantity(item)"
                                                                    class="block w-12 border-0 p-0 text-xs text-slate-900 focus:ring-0 bg-slate-50 rounded text-center">
                                                                <span class="text-[10px] text-slate-400">ft</span>
                                                            </div>
                                                            <div class="flex items-center gap-1">
                                                                <input type="number" step="0.01" x-model="item.width"
                                                                    placeholder="W" @input="calculateQuantity(item)"
                                                                    class="block w-12 border-0 p-0 text-xs text-slate-900 focus:ring-0 bg-slate-50 rounded text-center">
                                                                <span class="text-[10px] text-slate-400">ft</span>
                                                            </div>
                                                        </div>
                                                        <span class="text-xs text-slate-400"
                                                            x-text="(item.length && item.width) ? (item.length * item.width).toFixed(2) : '='"></span>
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

                                                    <!-- Internal Note Icon for Custom Items -->
                                                    <template x-if="!item.product_id">
                                                        <button type="button" @click="openInternalNoteModal(item)"
                                                            class="ml-2 text-slate-400 hover:text-amber-500 transition-colors"
                                                            title="Add Internal Note">
                                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                                stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                            </svg>
                                                        </button>
                                                    </template>
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
                                    class="px-3 py-3 text-center text-xs font-medium text-slate-500 uppercase tracking-wider w-28">
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
                                        <template x-if="item.options && item.options.length > 0">
                                            <div class="flex flex-wrap gap-1 mt-1">
                                                <template x-for="opt in item.options">
                                                    <span
                                                        class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10"
                                                        x-text="opt.name + ': ' + opt.value"></span>
                                                </template>
                                            </div>
                                        </template>
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="text" x-model="item.size" placeholder="Size"
                                            class="block w-full border-0 p-0 text-sm text-slate-900 focus:ring-0 bg-transparent placeholder:text-slate-400"
                                            x-show="!item.showCalculator">

                                        <div x-show="item.showCalculator" class="flex items-center gap-1"
                                            style="display: none;">
                                            <div class="flex flex-col">
                                                <div class="flex items-center gap-1 mb-0.5">
                                                    <input type="number" step="0.01" x-model="item.length"
                                                        placeholder="L" @input="calculateQuantity(item)"
                                                        class="block w-12 border-0 p-0 text-xs text-slate-900 focus:ring-0 bg-slate-50 rounded text-center">
                                                    <span class="text-[10px] text-slate-400">ft</span>
                                                </div>
                                                <div class="flex items-center gap-1">
                                                    <input type="number" step="0.01" x-model="item.width"
                                                        placeholder="W" @input="calculateQuantity(item)"
                                                        class="block w-12 border-0 p-0 text-xs text-slate-900 focus:ring-0 bg-slate-50 rounded text-center">
                                                    <span class="text-[10px] text-slate-400">ft</span>
                                                </div>
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
                                        <!-- Internal Note Icon for Custom Items -->
                                        <template x-if="!item.product_id">
                                            <button type="button" @click="openInternalNoteModal(item)"
                                                class="ml-2 text-slate-400 hover:text-amber-500 transition-colors"
                                                title="Add Internal Note">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                        </template>
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

            <!-- Internal Note Modal -->
            <div x-show="internalNoteModal.isOpen" class="relative z-50" aria-labelledby="modal-title" role="dialog"
                aria-modal="true" style="display: none;">
                <div x-show="internalNoteModal.isOpen" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-500/75 transition-opacity">
                </div>

                <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                    <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                        <div x-show="internalNoteModal.isOpen" x-transition:enter="ease-out duration-300"
                            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                            x-transition:leave="ease-in duration-200"
                            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                            @click.away="closeInternalNoteModal()"
                            class="relative transform overflow-hidden rounded-xl bg-white px-4 pb-4 pt-5 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">

                            <div>
                                <div class="flex items-center justify-between mb-4">
                                    <div>
                                        <h3 class="text-lg font-bold text-slate-900">Internal Note</h3>
                                        <p class="text-xs text-indigo-600 mt-1"
                                            x-text="internalNoteModal.activeItem ? (internalNoteModal.activeItem.name || 'New Item') : ''">
                                        </p>
                                    </div>
                                    <button type="button" @click="closeInternalNoteModal()"
                                        class="text-slate-400 hover:text-slate-500">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>

                                <div class="space-y-4">
                                    <p class="text-sm text-slate-500">
                                        This note is internal and will not be visible to the client.
                                    </p>
                                    <template x-if="internalNoteModal.activeItem">
                                        <textarea x-model="internalNoteModal.activeItem.internal_note" rows="4"
                                            class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                            placeholder="Add internal details here..."></textarea>
                                    </template>
                                </div>

                                <div class="mt-5 sm:mt-6">
                                    <button type="button"
                                        class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                                        @click="closeInternalNoteModal()">
                                        Save Note
                                    </button>
                                </div>
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

                            <div v-if="configModal.product">
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
                                        Add to Estimate
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
                        <textarea x-model="estimate.client_note" name="client_note" rows="3"
                            class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                            placeholder="Message visible to client..."></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-medium uppercase tracking-wide text-slate-500 mb-1">Terms &
                            Conditions</label>
                        <textarea x-model="estimate.terms" name="terms" rows="3"
                            class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                            placeholder="Payment terms, validity..."></textarea>
                    </div>
                    <div x-show="hasCustomItems()" x-cloak>
                        <label class="block text-xs font-medium uppercase tracking-wide text-slate-500 mb-1">Internal
                            Note</label>
                        <textarea x-model="estimate.admin_note" name="admin_note" rows="3"
                            class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                            placeholder="Internal use only. Not visible to client."></textarea>
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

                        <!-- Coupon Section -->
                        <div class="flex justify-between items-center py-2 border-t border-slate-100 mt-2">
                            <dt class="text-slate-600">Coupon Code</dt>
                            <dd class="flex items-center gap-2">
                                <template x-if="!estimate.coupon_code_id">
                                    <div class="flex gap-1">
                                        <input type="text" x-model="couponInput" placeholder="Code"
                                            class="block w-24 rounded-md border-0 py-1 text-sm text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 text-center uppercase">
                                        <button type="button" @click="applyCoupon()"
                                            class="rounded bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-600 shadow-sm hover:bg-indigo-100 ring-1 ring-inset ring-indigo-200">Apply</button>
                                    </div>
                                </template>
                                <template x-if="estimate.coupon_code_id">
                                    <div
                                        class="flex items-center gap-2 bg-emerald-50 px-2 py-1 rounded-md ring-1 ring-emerald-200">
                                        <span class="text-xs font-bold text-emerald-700"
                                            x-text="appliedCouponCode"></span>
                                        <button type="button" @click="removeCoupon()"
                                            class="text-emerald-400 hover:text-emerald-600">
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="3">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                            </dd>
                        </div>
                        <div x-show="couponMessage" class="text-right text-[10px]"
                            :class="couponValid ? 'text-emerald-600' : 'text-rose-600'" x-text="couponMessage"></div>

                        <div class="flex justify-between items-center py-2">
                            <dt class="text-slate-600">Discount</dt>
                            <dd class="flex items-center gap-2">
                                <select x-model="estimate.discount_type" name="discount_type" @change="calculateTotals"
                                    class="text-xs border-0 bg-transparent py-0 pl-0 pr-7 text-slate-500 focus:ring-0">
                                    <option value="percentage">%</option>
                                    <option value="fixed">Fixed</option>
                                </select>
                                <input type="number" x-model="estimate.discount_value" name="discount_value"
                                    @input="calculateTotals"
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
                class="fixed bottom-0 left-0 right-0 bg-white/90 backdrop-blur-md border-t border-slate-200 p-4 sm:px-8 z-50 flex justify-end items-center gap-3 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">

                <div class="h-6 w-px bg-slate-200 mx-2"></div>
                <button type="button" @click="window.history.back()"
                    class="text-sm font-semibold leading-6 text-slate-600 hover:text-slate-900 transition-colors px-4">Cancel</button>
                <x-primary-button type="submit" class="px-8 py-2.5" x-bind:disabled="isSubmitting"
                    x-bind:class="{ 'opacity-75 cursor-not-allowed': isSubmitting }">
                    <div class="flex items-center">
                        <div x-show="isSubmitting" class="mr-2" style="display: none;">
                            <x-loading-spinner size="5" />
                        </div>
                        <span x-text="isSubmitting ? 'Creating...' : 'Create Estimate'"></span>
                    </div>
                </x-primary-button>
            </div>
        </form>
    </div>

    <!-- Shared Logic Component -->
    @include('components.estimate-builder-script')
</x-app-layout>
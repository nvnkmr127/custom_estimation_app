<x-app-layout>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

    <div class="sm:flex sm:items-center mb-8">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Edit Estimate #{{ $estimate->estimate_number }}
            </h1>
            <p class="mt-2 text-sm text-slate-500">Update estimate details, items, and pricing.</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            <div class="flex gap-2">
                <a href="{{ route('estimates.analytics', $estimate) }}"
                    class="block rounded-lg bg-indigo-50 px-4 py-2.5 text-center text-sm font-semibold text-indigo-700 shadow-sm ring-1 ring-inset ring-indigo-200 hover:bg-indigo-100 transition-all duration-200">
                    View Analytics
                </a>
                <a href="{{ route('estimates.index') }}"
                    class="block rounded-lg bg-white px-4 py-2.5 text-center text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition-all duration-200">
                    Cancel & Back
                </a>
            </div>
        </div>
    </div>

    <!-- Main Logic -->
    <script>
        window.estimateData = {!! json_encode($estimate) !!};
    </script>
    <div x-data="estimateBuilder(window.estimateData)" x-init="init()" class="pb-20">

        <form action="{{ route('estimates.update', $estimate) }}" method="POST" @submit.prevent="submitForm"
            class="space-y-8" novalidate>
            @csrf
            @method('PUT')
            <input type="hidden" name="last_update_timestamp" value="{{ $estimate->updated_at }}">

            <!-- Validation Errors Alert -->
            <div x-show="validationErrors.length > 0" x-cloak
                class="rounded-xl border-2 border-rose-200 bg-rose-50 p-6 shadow-sm">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-bold text-rose-900 mb-2">Please Complete Required Fields</h3>
                        <p class="text-sm text-rose-700 mb-4">
                            The following fields are required or incomplete. Please review and complete them before
                            saving:
                        </p>
                        <ul class="space-y-2">
                            <template x-for="(error, index) in validationErrors" :key="index">
                                <li class="flex items-start gap-2 text-sm">
                                    <svg class="h-5 w-5 text-rose-500 flex-shrink-0 mt-0.5" fill="currentColor"
                                        viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <div>
                                        <span class="font-semibold text-rose-900" x-text="error.location"></span>
                                        <span class="text-rose-700">-</span>
                                        <span class="text-rose-800" x-text="error.itemName"></span>
                                        <span class="text-rose-700">:</span>
                                        <span class="text-rose-600" x-text="error.message"></span>
                                    </div>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
            </div>

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
                    <div class="relative" x-data="{ open: false }" @click.away="open = false">
                        <button type="button" @click="open = !open"
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
                            <select id="client-search" x-ref="clientSearch" name="client_id" required
                                class="mt-2 block w-full rounded-lg border-slate-300 py-1.5 text-slate-900 shadow-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                                <option value="{{ $estimate->client_id }}">
                                    {{ $estimate->client->name ?? 'Search for a client or lead...' }}
                                </option>
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
                                <option value="{{ $template->id }}" {{ old('pdf_template_id', $estimate->pdf_template_id) == $template->id ? 'selected' : '' }}>
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
                        <!-- Type Selection (Radio or Toggle) - Disabled in Edit Mode -->
                        <div class="flex items-center space-x-4 mt-2">
                            <label class="flex items-center opacity-75 cursor-not-allowed">
                                <input type="radio" name="type" value="room_based" x-model="estimate.type" disabled
                                    class="h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-600 cursor-not-allowed">
                                <span class="ml-2 block text-sm font-medium text-slate-700">Room-Based</span>
                            </label>
                            <label class="flex items-center opacity-75 cursor-not-allowed">
                                <input type="radio" name="type" value="standard" x-model="estimate.type" disabled
                                    class="h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-600 cursor-not-allowed">
                                <span class="ml-2 block text-sm font-medium text-slate-700">Standard List</span>
                            </label>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Estimate type cannot be changed after creation.</p>
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
                        <button type="button" @click="openProductPicker(null)" x-show="estimate.type === 'standard'"
                            class="inline-flex items-center gap-x-1.5 rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                            <svg class="-ml-0.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path
                                    d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                            </svg>
                            Add Item
                        </button>
                        <button type="button" @click="addSection" x-show="estimate.type === 'room_based'"
                            class="inline-flex items-center gap-x-1.5 rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                            <svg class="-ml-0.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path
                                    d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                            </svg>
                            Add Room
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
                                    <div class="relative" x-data="{ pkgOpen: false }" @click.away="pkgOpen = false">
                                        <button type="button" @click="pkgOpen = !pkgOpen"
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
                                    <thead class="bg-slate-50/50">
                                        <tr>
                                            <th scope="col" class="w-10 py-4 px-3"></th>
                                            <th scope="col"
                                                class="px-3 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest w-16">
                                                Image
                                            </th>
                                            <th scope="col"
                                                class="px-3 py-4 text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest w-40">
                                                Unit Configuration</th>
                                            <th scope="col"
                                                class="px-3 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                                Item Details</th>
                                            <th scope="col"
                                                class="px-3 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest w-28">
                                                Size</th>
                                            <th scope="col"
                                                class="px-3 py-4 text-right text-[10px] font-bold text-slate-400 uppercase tracking-widest w-28">
                                                Price</th>
                                            <th scope="col"
                                                class="px-3 py-4 text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest w-32">
                                                Quantity</th>
                                            <th scope="col"
                                                class="px-3 py-4 text-right text-[10px] font-bold text-slate-400 uppercase tracking-widest w-32">
                                                Total</th>
                                            <th scope="col" class="relative px-3 py-4 w-12"><span
                                                    class="sr-only">Actions</span></th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-slate-200 section-items-sortable">
                                        <template x-for="(item, itemIndex) in section.items" :key="itemIndex">
                                            <tr class="group hover:bg-slate-50/50 transition-all duration-200">
                                                <td
                                                    class="px-3 py-4 text-center text-slate-300 group-hover:text-slate-400 cursor-move handle">
                                                    <svg class="h-4 w-4 mx-auto" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M4 8h16M4 16h16" />
                                                    </svg>
                                                </td>
                                                <td class="px-3 py-4 align-middle">
                                                    <template x-if="item.image_url">
                                                        <div class="relative h-12 w-12 mx-auto">
                                                            <img :src="item.image_url"
                                                                class="h-full w-full object-cover rounded-lg shadow-sm ring-1 ring-slate-200">
                                                        </div>
                                                    </template>
                                                    <template x-if="!item.image_url">
                                                        <div
                                                            class="h-12 w-12 bg-slate-50 rounded-lg mx-auto flex items-center justify-center ring-1 ring-slate-200 border border-dashed border-slate-300">
                                                            <svg class="h-6 w-6 text-slate-300" fill="none"
                                                                viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                            </svg>
                                                        </div>
                                                    </template>
                                                </td>
                                                <td class="px-3 py-4">
                                                    <div class="flex flex-col gap-2">
                                                        <!-- Initial State: Show Button if no unit type assigned -->
                                                        <template x-if="!item.unit_type_id && !item._showTypePicker">
                                                            <button type="button" @click="item._showTypePicker = true"
                                                                class="flex items-center justify-center w-full rounded-xl border-2 border-dashed border-slate-200 bg-slate-50/50 py-2.5 px-3 text-[10px] font-bold text-slate-500 hover:border-indigo-400 hover:text-indigo-600 hover:bg-white hover:shadow-sm transition-all uppercase tracking-widest leading-none">
                                                                <svg class="h-3 w-3 mr-1" fill="none"
                                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="3" d="M12 4v16m8-8H4" />
                                                                </svg>
                                                                Unit
                                                            </button>
                                                        </template>

                                                        <!-- Active State: Show Type Selection and Unit Selection -->
                                                        <template x-if="item.unit_type_id || item._showTypePicker">
                                                            <div class="space-y-2">
                                                                <div class="relative group">
                                                                    <select x-model="item.unit_type_id"
                                                                        @change="onUnitTypeChange(item)"
                                                                        :class="hasItemError(item, sectionIndex) && (!item.unit_type_id || item.unit_type_id === '') ? 'border-rose-300 bg-rose-50/50 ring-2 ring-rose-200' : 'border-slate-200 bg-slate-50/50'"
                                                                        class="block w-full rounded-lg py-1.5 px-2 text-[10px] font-bold text-slate-600 focus:ring-2 focus:ring-indigo-600 transition-all appearance-none cursor-pointer hover:bg-white">
                                                                        <option value="">Manual</option>
                                                                        <template
                                                                            x-for="type in getFilteredUnitTypes(sectionIndex)"
                                                                            :key="type.id">
                                                                            <option :value="String(type.id)"
                                                                                x-text="type.name"
                                                                                :selected="String(type.id) === String(item.unit_type_id)">
                                                                            </option>
                                                                        </template>
                                                                    </select>
                                                                    <div
                                                                        class="absolute inset-y-0 right-2 flex items-center pointer-events-none text-slate-400 group-hover:text-slate-600">
                                                                        <svg class="h-3 w-3" fill="none"
                                                                            viewBox="0 0 24 24" stroke="currentColor">
                                                                            <path stroke-linecap="round"
                                                                                stroke-linejoin="round" stroke-width="2"
                                                                                d="M19 9l-7 7-7-7" />
                                                                        </svg>
                                                                    </div>
                                                                </div>

                                                                <div class="relative">
                                                                    <template x-if="item.unit_type_id">
                                                                        <select x-model="item.unit_type"
                                                                            :class="hasItemError(item, sectionIndex) && (!item.unit_type || item.unit_type === '') ? 'border-rose-300 bg-rose-50/50 ring-2 ring-rose-200' : 'border-indigo-200 bg-indigo-50/30'"
                                                                            class="block w-full rounded-lg border-indigo-200 bg-indigo-50/30 py-1.5 px-2 text-[11px] font-bold text-indigo-700 focus:ring-2 focus:ring-indigo-600 transition-all appearance-none cursor-pointer hover:bg-indigo-50/50 shadow-sm text-center">
                                                                            <template
                                                                                x-for="u in getUnitsByTypeId(item.unit_type_id)"
                                                                                :key="u">
                                                                                <option :value="u" x-text="u"
                                                                                    :selected="u === item.unit_type">
                                                                                </option>
                                                                            </template>
                                                                        </select>
                                                                    </template>
                                                                    <template x-if="!item.unit_type_id">
                                                                        <input type="text" x-model="item.unit_type"
                                                                            placeholder="e.g. nos"
                                                                            :class="hasItemError(item, sectionIndex) && (!item.unit_type || item.unit_type === '') ? 'border-rose-300 bg-rose-50/50 ring-2 ring-rose-200' : 'border-slate-200 bg-slate-50'"
                                                                            class="block w-full rounded-lg py-1.5 px-2 text-[11px] font-bold text-slate-700 text-center focus:ring-2 focus:ring-indigo-600 shadow-sm">
                                                                    </template>
                                                                </div>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </td>
                                                <td class="px-3 py-4">
                                                    <input type="text" x-model="item.name" placeholder="Item Name"
                                                        class="block w-full border-0 p-0 text-sm font-bold text-slate-900 focus:ring-0 placeholder:text-slate-400 bg-transparent mb-1">
                                                    <input type="text" x-model="item.description"
                                                        placeholder="Description"
                                                        class="block w-full border-0 p-0 text-xs text-slate-500 focus:ring-0 placeholder:text-slate-400 bg-transparent">
                                                    <template x-if="item.options && item.options.length > 0">
                                                        <div class="flex flex-wrap gap-1.5 mt-2">
                                                            <template x-for="opt in item.options">
                                                                <span
                                                                    class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600 ring-1 ring-inset ring-slate-200"
                                                                    x-text="opt.name + ': ' + opt.value"></span>
                                                            </template>
                                                        </div>
                                                    </template>
                                                </td>
                                                <td class="px-3 py-4">
                                                    <div class="relative">
                                                        <input type="text" x-model="item.size" placeholder="Enter Size"
                                                            class="block w-full rounded-lg border-slate-200 bg-slate-50/50 py-1.5 px-3 text-sm text-slate-900 focus:ring-2 focus:ring-indigo-600 transition-all placeholder:text-slate-400"
                                                            x-show="!item.showCalculator">


                                                        <div x-show="item.showCalculator"
                                                            class="flex items-center gap-2 bg-slate-50 p-2 rounded-lg border border-slate-200"
                                                            style="display: none;">
                                                            <div class="flex flex-col gap-1.5">
                                                                <div class="flex items-center gap-2">
                                                                    <span
                                                                        class="text-[10px] font-bold text-slate-600 uppercase w-3">L</span>
                                                                    <input type="number" step="0.01"
                                                                        x-model="item.length" placeholder="0"
                                                                        @input="calculateQuantity(item)"
                                                                        class="block w-14 rounded border-slate-200 py-1 px-1.5 text-center text-xs text-slate-900 focus:ring-indigo-600">
                                                                    <span
                                                                        class="text-[10px] font-bold text-slate-400 uppercase">ft</span>
                                                                </div>
                                                                <div class="flex items-center gap-2">
                                                                    <span
                                                                        class="text-[10px] font-bold text-slate-600 uppercase w-3">W</span>
                                                                    <input type="number" step="0.01"
                                                                        x-model="item.width" placeholder="0"
                                                                        @input="calculateQuantity(item)"
                                                                        class="block w-14 rounded border-slate-200 py-1 px-1.5 text-center text-xs text-slate-900 focus:ring-indigo-600">
                                                                    <span
                                                                        class="text-[10px] font-bold text-slate-400 uppercase">ft</span>
                                                                </div>
                                                                <div class="flex items-center gap-2">
                                                                    <span
                                                                        class="text-[10px] font-bold text-slate-600 uppercase w-3">H</span>
                                                                    <input type="number" step="0.01"
                                                                        x-model="item.height" placeholder="0"
                                                                        class="block w-14 rounded border-slate-200 py-1 px-1.5 text-center text-xs text-slate-900 focus:ring-indigo-600">
                                                                    <span
                                                                        class="text-[10px] font-bold text-slate-400 uppercase">ft</span>
                                                                </div>
                                                            </div>
                                                            <div class="flex flex-col items-center">
                                                                <span
                                                                    class="text-[10px] font-bold text-slate-300 mb-0.5">AREA</span>
                                                                <span
                                                                    class="text-xs font-bold text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded"
                                                                    x-text="(item.length && item.width) ? (item.length * item.width).toFixed(2) : '0.00'"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-3 py-4">
                                                    <div class="relative">
                                                        <span
                                                            class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs font-medium"
                                                            x-text="estimate.currency"></span>
                                                        <input type="number" step="0.01" x-model="item.unit_price"
                                                            @input="calculateTotals"
                                                            class="block w-full rounded-lg border-slate-200 bg-slate-50/50 py-1.5 pl-8 pr-3 text-sm text-slate-900 text-right font-medium focus:ring-2 focus:ring-indigo-600 transition-all">
                                                    </div>
                                                </td>
                                                <td class="px-3 py-4">
                                                    <div class="flex items-center justify-center gap-1.5">
                                                        <input type="number" step="0.01" x-model="item.quantity"
                                                            @input="calculateTotals"
                                                            class="block w-20 rounded-lg border-slate-200 py-1.5 text-center text-sm font-bold text-slate-900 focus:ring-2 focus:ring-indigo-600 transition-all"
                                                            :readonly="item.showCalculator"
                                                            :class="{'bg-slate-100 italic text-slate-400 border-dashed': item.showCalculator}">
                                                        <button type="button" @click="toggleCalculator(item)"
                                                            class="flex items-center justify-center h-8 w-8 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition-all border border-transparent hover:border-indigo-100"
                                                            :class="{'text-indigo-600 bg-indigo-50 border-indigo-100': item.showCalculator}"
                                                            title="Toggle Area Calculator">
                                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                                stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </td>
                                                <td class="px-3 py-4 text-right align-middle">
                                                    <div class="flex flex-col">
                                                        <span
                                                            class="text-[10px] font-bold text-slate-400 uppercase leading-none mb-1">Total</span>
                                                        <span class="text-sm font-bold text-slate-900"
                                                            x-text="estimate.currency + ' ' + calculateItemTotal(item).toFixed(2)"></span>
                                                    </div>
                                                </td>
                                                <td class="px-3 py-4 text-right">
                                                    <div class="flex flex-col items-center gap-3">
                                                        <button type="button"
                                                            @click="removeItem(sectionIndex, itemIndex)"
                                                            class="group/del h-8 w-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-all border border-transparent hover:border-rose-100">
                                                            <svg class="h-5 w-5" viewBox="0 0 20 20"
                                                                fill="currentColor">
                                                                <path fill-rule="evenodd"
                                                                    d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4z"
                                                                    clip-rule="evenodd" />
                                                            </svg>
                                                        </button>

                                                        <!-- Internal Note Icon for Custom Items -->
                                                        <template x-if="!item.product_id">
                                                            <button type="button" @click="openInternalNoteModal(item)"
                                                                class="h-8 w-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-amber-500 hover:bg-amber-50 transition-all border border-transparent hover:border-amber-100"
                                                                title="Add Internal Note">
                                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                                    stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
                                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                                </svg>
                                                            </button>
                                                        </template>
                                                    </div>
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

                    <button type="button" @click="openProductPicker(null)"
                        class="w-full py-4 border-2 border-dashed border-slate-300 rounded-xl text-slate-400 hover:border-indigo-500 hover:text-indigo-600 hover:bg-indigo-50 transition-all font-medium flex items-center justify-center gap-2">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path
                                d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                        </svg>
                        Add Item
                    </button>
                </div>

                <!-- Standard List Table -->
                <div x-show="estimate.type === 'standard'" class="overflow-x-auto min-h-[100px]">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50/50">
                            <tr>
                                <th scope="col" class="w-10 py-4 px-3"></th>
                                <th scope="col"
                                    class="px-3 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest w-16">
                                    Image
                                </th>
                                <th scope="col"
                                    class="px-3 py-4 text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest w-40">
                                    Unit Configuration</th>
                                <th scope="col"
                                    class="px-3 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                    Item Details</th>
                                <th scope="col"
                                    class="px-3 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest w-28">
                                    Size</th>
                                <th scope="col"
                                    class="px-3 py-4 text-right text-[10px] font-bold text-slate-400 uppercase tracking-widest w-28">
                                    Price</th>
                                <th scope="col"
                                    class="px-3 py-4 text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest w-32">
                                    Quantity</th>
                                <th scope="col"
                                    class="px-3 py-4 text-right text-[10px] font-bold text-slate-400 uppercase tracking-widest w-32">
                                    Total</th>
                                <th scope="col" class="relative px-3 py-4 w-12"><span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200 standard-items-sortable">
                            <template x-for="(item, itemIndex) in estimate.items" :key="itemIndex">
                                <tr class="group hover:bg-slate-50/50 transition-all duration-200">
                                    <td
                                        class="px-3 py-4 text-center text-slate-300 group-hover:text-slate-400 cursor-move handle">
                                        <svg class="h-4 w-4 mx-auto" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 8h16M4 16h16" />
                                        </svg>
                                    </td>
                                    <td class="px-3 py-4 vertical-align-middle">
                                        <template x-if="item.image_url">
                                            <div class="relative h-12 w-12 mx-auto">
                                                <img :src="item.image_url"
                                                    class="h-full w-full object-cover rounded-lg shadow-sm ring-1 ring-slate-200">
                                            </div>
                                        </template>
                                        <template x-if="!item.image_url">
                                            <div
                                                class="h-12 w-12 bg-slate-50 rounded-lg mx-auto flex items-center justify-center ring-1 ring-slate-200 border border-dashed border-slate-300">
                                                <svg class="h-6 w-6 text-slate-300" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                        </template>
                                    </td>
                                    <td class="px-3 py-4">
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
                                                    <div class="relative group">
                                                        <select x-model="item.unit_type_id"
                                                            @change="onUnitTypeChange(item)"
                                                            :class="hasItemError(item, null) && (!item.unit_type_id || item.unit_type_id === '') ? 'border-rose-300 bg-rose-50/50 ring-2 ring-rose-200' : 'border-slate-200 bg-slate-50/50'"
                                                            class="block w-full rounded-lg py-1.5 px-2 text-[10px] font-bold text-slate-600 focus:ring-2 focus:ring-indigo-600 transition-all appearance-none cursor-pointer hover:bg-white">
                                                            <option value="">Manual</option>
                                                            <template x-for="type in unitTypes" :key="type.id">
                                                                <option :value="type.id" x-text="type.name"></option>
                                                            </template>
                                                        </select>
                                                        <div
                                                            class="absolute inset-y-0 right-2 flex items-center pointer-events-none text-slate-400 group-hover:text-slate-600">
                                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24"
                                                                stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M19 9l-7 7-7-7" />
                                                            </svg>
                                                        </div>
                                                    </div>

                                                    <div class="relative">
                                                        <template x-if="item.unit_type_id">
                                                            <select x-model="item.unit_type"
                                                                :class="hasItemError(item, null) && (!item.unit_type || item.unit_type === '') ? 'border-rose-300 bg-rose-50/50 ring-2 ring-rose-200' : 'border-indigo-200 bg-indigo-50/30'"
                                                                class="block w-full rounded-lg py-1.5 px-2 text-[11px] font-bold text-indigo-700 focus:ring-2 focus:ring-indigo-600 transition-all appearance-none cursor-pointer hover:bg-indigo-50/50 shadow-sm text-center">
                                                                <template
                                                                    x-for="u in getUnitsByTypeId(item.unit_type_id)"
                                                                    :key="u">
                                                                    <option :value="u" x-text="u"></option>
                                                                </template>
                                                            </select>
                                                        </template>
                                                        <template x-if="!item.unit_type_id">
                                                            <input type="text" x-model="item.unit_type"
                                                                placeholder="e.g. nos"
                                                                :class="hasItemError(item, null) && (!item.unit_type || item.unit_type === '') ? 'border-rose-300 bg-rose-50/50 ring-2 ring-rose-200' : 'border-slate-200 bg-slate-50'"
                                                                class="block w-full rounded-lg py-1.5 px-2 text-[11px] font-bold text-slate-700 text-center focus:ring-2 focus:ring-indigo-600 shadow-sm">
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </td>
                                    <td class="px-3 py-4">
                                        <input type="text" x-model="item.name" placeholder="Item Name"
                                            class="block w-full border-0 p-0 text-sm font-bold text-slate-900 focus:ring-0 placeholder:text-slate-400 bg-transparent mb-1">
                                        <input type="text" x-model="item.description" placeholder="Description"
                                            class="block w-full border-0 p-0 text-xs text-slate-500 focus:ring-0 placeholder:text-slate-400 bg-transparent">
                                        <template x-if="item.options && item.options.length > 0">
                                            <div class="flex flex-wrap gap-1.5 mt-2">
                                                <template x-for="opt in item.options">
                                                    <span
                                                        class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600 ring-1 ring-inset ring-slate-200"
                                                        x-text="opt.name + ': ' + opt.value"></span>
                                                </template>
                                            </div>
                                        </template>
                                    </td>
                                    <td class="px-3 py-4">
                                        <div class="relative">
                                            <input type="text" x-model="item.size" placeholder="Enter Size"
                                                class="block w-full rounded-lg border-slate-200 bg-slate-50/50 py-1.5 px-3 text-sm text-slate-900 focus:ring-2 focus:ring-indigo-600 transition-all placeholder:text-slate-400"
                                                x-show="!item.showCalculator">


                                            <div x-show="item.showCalculator"
                                                class="flex items-center gap-2 bg-slate-50 p-2 rounded-lg border border-slate-200"
                                                style="display: none;">
                                                <div class="flex flex-col gap-1.5">
                                                    <div class="flex items-center gap-2">
                                                        <span
                                                            class="text-[10px] font-bold text-slate-600 uppercase w-3">L</span>
                                                        <input type="number" step="0.01" x-model="item.length"
                                                            placeholder="0" @input="calculateQuantity(item)"
                                                            class="block w-14 rounded border-slate-200 py-1 px-1.5 text-center text-xs text-slate-900 focus:ring-indigo-600">
                                                        <span
                                                            class="text-[10px] font-bold text-slate-400 uppercase">ft</span>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <span
                                                            class="text-[10px] font-bold text-slate-600 uppercase w-3">W</span>
                                                        <input type="number" step="0.01" x-model="item.width"
                                                            placeholder="0" @input="calculateQuantity(item)"
                                                            class="block w-14 rounded border-slate-200 py-1 px-1.5 text-center text-xs text-slate-900 focus:ring-indigo-600">
                                                        <span
                                                            class="text-[10px] font-bold text-slate-400 uppercase">ft</span>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <span
                                                            class="text-[10px] font-bold text-slate-600 uppercase w-3">H</span>
                                                        <input type="number" step="0.01" x-model="item.height"
                                                            placeholder="0"
                                                            class="block w-14 rounded border-slate-200 py-1 px-1.5 text-center text-xs text-slate-900 focus:ring-indigo-600">
                                                        <span
                                                            class="text-[10px] font-bold text-slate-400 uppercase">ft</span>
                                                    </div>
                                                </div>
                                                <div class="flex flex-col items-center">
                                                    <span
                                                        class="text-[10px] font-bold text-slate-300 mb-0.5">AREA</span>
                                                    <span
                                                        class="text-xs font-bold text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded"
                                                        x-text="(item.length && item.width) ? (item.length * item.width).toFixed(2) : '0.00'"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-4">
                                        <div class="relative">
                                            <span
                                                class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs font-medium"
                                                x-text="estimate.currency"></span>
                                            <input type="number" step="0.01" x-model="item.unit_price"
                                                @input="calculateTotals"
                                                class="block w-full rounded-lg border-slate-200 bg-slate-50/50 py-1.5 pl-8 pr-3 text-sm text-slate-900 text-right font-medium focus:ring-2 focus:ring-indigo-600 transition-all">
                                        </div>
                                    </td>
                                    <td class="px-3 py-4">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <input type="number" step="0.01" x-model="item.quantity"
                                                @input="calculateTotals"
                                                class="block w-20 rounded-lg border-slate-200 py-1.5 text-center text-sm font-bold text-slate-900 focus:ring-2 focus:ring-indigo-600 transition-all"
                                                :readonly="item.showCalculator"
                                                :class="{'bg-slate-100 italic text-slate-400 border-dashed': item.showCalculator}">
                                            <button type="button" @click="toggleCalculator(item)"
                                                class="flex items-center justify-center h-8 w-8 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition-all border border-transparent hover:border-indigo-100"
                                                :class="{'text-indigo-600 bg-indigo-50 border-indigo-100': item.showCalculator}"
                                                title="Toggle Area Calculator">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-3 py-4 text-right vertical-align-middle">
                                        <div class="flex flex-col">
                                            <span
                                                class="text-[10px] font-bold text-slate-400 uppercase leading-none mb-1">Total</span>
                                            <span class="text-sm font-bold text-slate-900"
                                                x-text="estimate.currency + ' ' + calculateItemTotal(item).toFixed(2)"></span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-4 text-right">
                                        <div class="flex flex-col items-center gap-3">
                                            <button type="button" @click="removeItem(null, itemIndex)"
                                                class="group/del h-8 w-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-all border border-transparent hover:border-rose-100">
                                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd"
                                                        d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </button>

                                            <!-- Internal Note Icon for Custom Items -->
                                            <template x-if="!item.product_id">
                                                <button type="button" @click="openInternalNoteModal(item)"
                                                    class="h-8 w-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-amber-500 hover:bg-amber-50 transition-all border border-transparent hover:border-amber-100"
                                                    title="Add Internal Note">
                                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </button>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <button type="button" @click="openProductPicker(null)" x-show="estimate.type === 'standard'"
                    class="h-full min-h-[160px] border-2 border-dashed border-slate-300 rounded-xl text-slate-400 hover:border-indigo-500 hover:text-indigo-600 hover:bg-indigo-50 transition-all font-medium flex flex-col items-center justify-center gap-2">
                    <svg class="h-8 w-8" viewBox="0 0 20 20" fill="currentColor">
                        <path
                            d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                    </svg>
                    <span>Add Item</span>
                </button>

                <button type="button" @click="addSection" x-show="estimate.type === 'room_based'"
                    class="h-full min-h-[160px] w-full border-2 border-dashed border-slate-300 rounded-xl text-slate-400 hover:border-indigo-500 hover:text-indigo-600 hover:bg-indigo-50 transition-all font-medium flex flex-col items-center justify-center gap-2">
                    <svg class="h-8 w-8" viewBox="0 0 20 20" fill="currentColor">
                        <path
                            d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                    </svg>
                    <span>Add New Room</span>
                </button>
            </div>

            <!-- Product Picker Modal -->
            <x-estimates.product-picker />

            <!-- Internal Note Modal -->
            <x-estimates.internal-note-modal />

            <!-- Product Configuration Modal -->
            <x-estimates.config-modal />

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

                        <div class="flex justify-between items-center py-2 border-t border-slate-100 mt-2">
                            <dt class="text-slate-600 font-semibold uppercase text-[10px] tracking-wider">Taxes</dt>
                            <dd class="flex items-center gap-4">
                                <div class="flex flex-col items-end">
                                    <label class="text-[10px] text-slate-400 mb-0.5">Tax 1 (%)</label>
                                    <input type="number" step="0.01" x-model="estimate.tax_1" name="tax_1"
                                        @input="calculateTotals"
                                        class="block w-20 rounded-md border-0 py-1 text-right text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 sm:text-sm sm:leading-6">
                                </div>
                                <div class="flex flex-col items-end">
                                    <label class="text-[10px] text-slate-400 mb-0.5">Tax 2 (%)</label>
                                    <input type="number" step="0.01" x-model="estimate.tax_2" name="tax_2"
                                        @input="calculateTotals"
                                        class="block w-20 rounded-md border-0 py-1 text-right text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 sm:text-sm sm:leading-6">
                                </div>
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
                        <span x-text="isSubmitting ? 'Updating...' : 'Update Estimate'"></span>
                    </div>
                </x-primary-button>
            </div>
        </form>
    </div>

    <!-- Shared Logic Component -->
    @include('components.estimate-builder-script', [
        'products' => $products,
        'templates' => $templates,
        'packages' => $packages,
        'unitTypes' => $unitTypes,
        'categories' => $categories,
        'defaults' => $defaults
    ])
</x-app-layout>
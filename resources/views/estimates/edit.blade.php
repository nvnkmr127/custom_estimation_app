<x-app-layout>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

    <script>
        window.estimateData = {!! json_encode($estimate) !!};
        window.estimateData.isSuperAdmin = @json(auth()->user()->hasRole('super_admin'));
    </script>
    <div x-data="estimateBuilder(window.estimateData)" x-init="init()" class="pb-20">
        <div class="sm:flex sm:items-center mb-8">
            <div class="sm:flex-auto flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Edit Estimate
                        #{{ $estimate->estimate_number }}
                    </h1>
                    <p class="mt-2 text-sm text-slate-500">Update estimate details, items, and pricing.</p>
                </div>

            </div>
            <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                <div class="flex items-center gap-4">


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
        </div>

        <!-- Main Logic -->

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

                    <!-- Restore Button in Top Bar -->
                    <button type="button" @click="showDeletedModal = true"
                        id="restore-button-top"
                        class="inline-flex items-center gap-x-1.5 rounded-md bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-600 shadow-sm ring-1 ring-inset ring-amber-200 hover:bg-amber-100 transition-all">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Restore (<span x-text="deletedItems.length + deletedSections.length"></span>)
                    </button>
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
                                <option value="">Select a client...</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" {{ $estimate->client_id == $client->id ? 'selected' : '' }}>
                                        {{ $client->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Client & Property Details Display -->
                        <div x-show="selectedClient" x-transition
                            class="mt-4 p-4 rounded-xl bg-slate-50 border border-slate-200">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <span
                                        class="block text-[10px] uppercase tracking-wider font-bold text-slate-500">Contact
                                        Details</span>
                                    <p class="text-sm font-semibold text-slate-900" x-text="selectedClient?.name"></p>
                                    <p class="text-xs text-slate-600" x-text="selectedClient?.email"></p>
                                </div>
                                <div x-show="selectedClient?.property_name || selectedClient?.property_address">
                                    <span
                                        class="block text-[10px] uppercase tracking-wider font-bold text-slate-500">Property
                                        Details</span>
                                    <p class="text-sm font-semibold text-slate-900"
                                        x-text="selectedClient?.property_name"></p>
                                    <p class="text-xs text-slate-600" x-text="selectedClient?.property_address"></p>
                                </div>
                            </div>
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

                    <input type="hidden" name="currency" x-model="estimate.currency">

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
                        <button type="button" @click.stop="openProductPicker(null)"
                            x-show="estimate.type === 'standard'"
                            class="inline-flex items-center gap-x-1.5 rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                            <svg class="-ml-0.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path
                                    d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                            </svg>
                            Add Item
                        </button>
                        <button type="button" @click="openRoomModal()" x-show="estimate.type === 'room_based'"
                            class="inline-flex items-center gap-x-1.5 rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                            <svg class="-ml-0.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path
                                    d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                            </svg>
                            Add Room
                        </button>

                        <div class="relative" x-data="{ pkgOpen: false }" @click.away="pkgOpen = false"
                            x-show="estimate.type === 'room_based'">
                            <button type="button" @click="pkgOpen = !pkgOpen"
                                class="inline-flex items-center gap-x-1.5 rounded-md bg-white px-3 py-2 text-sm font-semibold text-indigo-600 shadow-sm ring-1 ring-inset ring-indigo-300 hover:bg-indigo-50">
                                <svg class="-ml-0.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path
                                        d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                                </svg>
                                Add Package
                            </button>
                            <div x-show="pkgOpen"
                                class="absolute right-0 z-10 mt-2 w-56 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                                style="display: none;">
                                <div class="py-1">
                                    <template x-for="pkg in packages" :key="pkg.id">
                                        <button type="button" @click="applyPackage(pkg, null); pkgOpen = false"
                                            class="block w-full px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-100 hover:text-slate-900"
                                            x-text="pkg.name"></button>
                                    </template>
                                    <div x-show="packages.length === 0" class="px-4 py-2 text-sm text-slate-500">No
                                        packages available</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Room-Based List -->
                <div x-show="estimate.type === 'room_based'" class="space-y-6 sections-container">
                    <template x-for="(section, sectionIndex) in estimate.sections" :key="sectionIndex">
                        <div class="section-card border border-slate-200 rounded-xl bg-white shadow-sm overflow-hidden"
                            :data-section-index="sectionIndex">
                            <!-- Section Header -->
                            <div
                                class="bg-slate-50 border-b border-slate-200 px-4 py-3 sm:px-6 flex items-center justify-between gap-4">
                                <div class="flex items-center gap-3 flex-1">
                                    <div class="section-handle cursor-move text-slate-400 hover:text-slate-600">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 8h16M4 16h16" />
                                        </svg>
                                    </div>
                                    <input type="text" x-model="section.name" placeholder="Section Name" readonly
                                        class="block w-full text-lg font-bold bg-transparent border-0 p-0 text-slate-900 focus:ring-0 placeholder:text-slate-400 cursor-default">
                                    <template x-if="section.section_type === 'package'">
                                        <span
                                            class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-700 ring-1 ring-inset ring-indigo-200 uppercase tracking-widest leading-none">
                                            Package
                                        </span>
                                    </template>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" @click.stop="openProductPicker(sectionIndex)"
                                        x-show="section.section_type === 'room'"
                                        class="text-sm font-medium text-slate-600 hover:text-indigo-600">
                                        + Add Item
                                    </button>
                                    <button type="button" @click="removeSection(sectionIndex)"
                                        class="text-sm font-medium text-rose-600 hover:text-rose-900 ml-2">
                                        Remove Section
                                    </button>
                                </div>
                            </div>

                            <!-- ROOM LAYOUT TABLE -->
                            <div class="overflow-x-auto min-h-[100px]" x-show="section.section_type === 'room'">
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
                                                Unit Config</th>
                                            <th scope="col"
                                                class="px-3 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest w-80">
                                                Item Details</th>
                                            <th scope="col"
                                                class="px-3 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest w-36">
                                                Dimensions</th>
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
                                    <tbody class="bg-white divide-y divide-slate-200 section-items-sortable"
                                        :data-section-index="sectionIndex">
                                        <template x-for="(item, itemIndex) in section.items"
                                            :key="item._uid || item.id || itemIndex">
                                            <tr class="item-row group hover:bg-slate-50/50 transition-all duration-200">
                                                <!-- Handle -->
                                                <td
                                                    class="px-3 py-4 text-center text-slate-300 group-hover:text-slate-400 cursor-move handle">
                                                    <svg class="h-4 w-4 mx-auto" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M4 8h16M4 16h16" />
                                                    </svg>
                                                </td>
                                                <!-- Image -->
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
                                                <!-- Column 3: Unit Configuration -->
                                                <td class="px-3 py-4">
                                                    <div class="flex flex-col gap-2">
                                                        <div class="relative group">
                                                            <select :value="item.unit_type_id ? String(item.unit_type_id) : ''" @change="item.unit_type_id = $event.target.value; onUnitTypeChange(item)"
                                                                class="block w-full rounded-lg border-slate-200 bg-white py-1.5 px-2 text-[10px] font-bold text-slate-600 focus:ring-2 focus:ring-indigo-600 transition-all appearance-none cursor-pointer hover:bg-slate-50 shadow-sm">
                                                                <option value="">Manual Unit Type</option>
                                                                <template x-for="type in unitTypes" :key="'room_'+type.id">
                                                                    <option :value="String(type.id)" x-text="type.name" :selected="item.unit_type_id && String(item.unit_type_id) === String(type.id)"></option>
                                                                </template>
                                                            </select>
                                                            <div
                                                                class="absolute inset-y-0 right-2 flex items-center pointer-events-none text-slate-400">
                                                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24"
                                                                    stroke="currentColor" stroke-width="2">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        d="M19 9l-7 7-7-7" />
                                                                </svg>
                                                            </div>
                                                        </div>
                                                        <div class="relative">
                                                            <template x-if="item.unit_type_id">
                                                                <select x-model="item.unit_type"
                                                                    class="block w-full rounded-lg border-indigo-200 bg-indigo-50/30 py-1 px-2 text-[10px] font-bold text-indigo-700 focus:ring-2 focus:ring-indigo-600 transition-all appearance-none cursor-pointer hover:bg-indigo-50/50 shadow-sm text-center">
                                                                    <template
                                                                        x-for="u in getUnitsByTypeId(item.unit_type_id)"
                                                                        :key="u">
                                                                        <option :value="u" x-text="u"
                                                                            :selected="u === item.unit_type"></option>
                                                                    </template>
                                                                </select>
                                                            </template>
                                                            <template x-if="!item.unit_type_id">
                                                                <input type="text" x-model="item.unit_type"
                                                                    placeholder="Unit (e.g. nos)"
                                                                    class="block w-full rounded-lg border-slate-200 py-1 px-2 text-[10px] font-bold text-slate-700 text-center focus:ring-2 focus:ring-indigo-600 shadow-sm">
                                                            </template>
                                                        </div>
                                                    </div>
                                                </td>
                                                <!-- Unit Config -->
                                                <!-- Column 4: Item Details -->
                                                <td class="px-3 py-4">
                                                    <input type="text" x-model="item.name" placeholder="Item Name"
                                                        :readonly="isItemLocked(item)"
                                                        :class="isItemLocked(item) ? 'cursor-default text-slate-500' : ''"
                                                        class="block w-full border-0 p-0 text-sm font-bold text-slate-900 focus:ring-0 placeholder:text-slate-400 bg-transparent mb-1">
                                                    <textarea x-model="item.description" placeholder="Description"
                                                        rows="2" :readonly="isItemLocked(item)"
                                                        :class="isItemLocked(item) ? 'cursor-default text-slate-500' : ''"
                                                        class="block w-full border-0 p-0 text-xs text-slate-500 focus:ring-0 placeholder:text-slate-400 bg-transparent resize-none leading-relaxed"></textarea>

                                                    <!-- Variant Dropdowns placed exactly below description -->
                                                    <template
                                                        x-if="item.product_id && getProduct(item.product_id) && getProduct(item.product_id).options && getProduct(item.product_id).options.length > 0">
                                                        <div class="flex flex-wrap gap-x-4 gap-y-2 mt-2">
                                                            <template
                                                                x-for="option in getProduct(item.product_id).options"
                                                                :key="option.id">
                                                                <div class="flex flex-col gap-0.5 min-w-[120px]">
                                                                    <span
                                                                        class="text-[9px] font-bold text-slate-400 uppercase tracking-widest pl-1"
                                                                        x-text="option.name"></span>
                                                                    <div class="relative group/opt">
                                                                        <select
                                                                            @change="updateItemOption(item, option.id, $event.target.value)"
                                                                            class="block w-full rounded-lg border-indigo-100 bg-white py-1 px-3 text-[11px] font-bold text-indigo-700 focus:ring-2 focus:ring-indigo-600 focus:border-indigo-600 transition-all appearance-none cursor-pointer hover:bg-indigo-50/30 shadow-sm pr-8">
                                                                            <template x-for="val in option.values"
                                                                                :key="val.id">
                                                                                <option :value="val.id"
                                                                                    :selected="item.selected_options[option.id] == val.id"
                                                                                    x-text="val.value"></option>
                                                                            </template>
                                                                        </select>
                                                                        <div
                                                                            class="absolute inset-y-0 right-2 flex items-center pointer-events-none text-indigo-400 group-hover/opt:text-indigo-600">
                                                                            <svg class="h-3 w-3" fill="none"
                                                                                viewBox="0 0 24 24"
                                                                                stroke="currentColor" stroke-width="2">
                                                                                <path stroke-linecap="round"
                                                                                    stroke-linejoin="round"
                                                                                    d="M19 9l-7 7-7-7" />
                                                                            </svg>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </template>

                                                    <!-- Fallback Badge for Custom/Manual Options -->
                                                    <template
                                                        x-if="(!item.product_id || !getProduct(item.product_id)?.options?.length) && item.options && item.options.length > 0">
                                                        <div class="flex flex-wrap gap-1.5 mt-2">
                                                            <template x-for="opt in item.options">
                                                                <span
                                                                    class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600 ring-1 ring-inset ring-slate-200"
                                                                    x-text="opt.name + ': ' + opt.value"></span>
                                                            </template>
                                                        </div>
                                                    </template>
                                                </td>
                                                <!-- Column 5: Dimensions -->
                                                <td class="px-3 py-4">
                                                    <div class="flex flex-col gap-2">
                                                        <div
                                                            class="flex items-center gap-2 bg-slate-50/80 p-2 rounded-lg border border-slate-200 shadow-inner">
                                                            <div class="flex flex-col gap-1.5">
                                                                <div class="flex items-center gap-2">
                                                                    <span
                                                                        class="text-[10px] font-bold text-slate-600 uppercase w-3">L</span>
                                                                    <input type="number" step="0.01"
                                                                        x-model="item.length" placeholder="0"
                                                                        @input="calculateSize(item)"
                                                                        class="block w-14 rounded border-slate-200 py-1 px-1.5 text-center text-xs text-slate-900 focus:ring-indigo-600">
                                                                </div>
                                                                <div class="flex items-center gap-2">
                                                                    <span
                                                                        class="text-[10px] font-bold text-slate-600 uppercase w-3">W</span>
                                                                    <input type="number" step="0.01"
                                                                        x-model="item.width" placeholder="0"
                                                                        @input="calculateSize(item)"
                                                                        class="block w-14 rounded border-slate-200 py-1 px-1.5 text-center text-xs text-slate-900 focus:ring-indigo-600">
                                                                </div>
                                                                <div class="flex items-center gap-2">
                                                                    <span
                                                                        class="text-[10px] font-bold text-slate-600 uppercase w-3">H</span>
                                                                    <input type="number" step="0.01"
                                                                        x-model="item.height" placeholder="0"
                                                                        @input="calculateSize(item)"
                                                                        class="block w-14 rounded border-slate-200 py-1 px-1.5 text-center text-xs text-slate-900 focus:ring-indigo-600">
                                                                </div>
                                                            </div>
                                                            <div
                                                                class="flex flex-col items-center flex-1 border-l border-slate-200 pl-2">
                                                                <span
                                                                    class="text-[9px] font-bold text-slate-400 mb-0.5 tracking-tight"
                                                                    x-text="item.formula === 'volume' ? 'VOLUME' : (item.formula === 'area_lh' ? 'AREA' : (item.formula === 'formula' ? 'CUSTOM' : 'AREA'))"></span>
                                                                <span
                                                                    class="text-xs font-black text-indigo-600 leading-none"
                                                                    x-text="parseFloat(item.size || 0).toFixed(2)"></span>
                                                                <span class="text-[8px] font-bold text-slate-400 mt-0.5"
                                                                    x-text="item.unit_type || 'sqft'"></span>
                                                            </div>
                                                        </div>

                                                    </div>
                            </div>
                        </div>
                        </td>
                        <!-- Price -->
                        <td class="px-3 py-4">
                            <div class="relative">
                                <span
                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs font-medium"
                                    x-text="estimate.currency_symbol"></span>
                                <input type="number" step="0.01" x-model="item.unit_price"
                                    :readonly="isItemLocked(item)" @input="calculateTotals"
                                    :class="isItemLocked(item) ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'bg-slate-50/50 text-slate-900 focus:ring-2 focus:ring-indigo-600'"
                                    class="block w-full rounded-lg border-slate-200 py-1.5 pl-8 pr-3 text-sm text-right font-medium transition-all">
                            </div>
                        </td>
                        <!-- Quantity -->
                        <td class="px-3 py-4">
                            <div class="flex items-center justify-center gap-1.5">
                                <input type="number" step="0.01" x-model="item.quantity" @input="calculateTotals"
                                    class="block w-20 rounded-lg border-slate-200 py-1.5 text-center text-sm font-bold text-slate-900 focus:ring-2 focus:ring-indigo-600 transition-all">
                            </div>
                        </td>
                        <!-- Total -->
                        <td class="px-3 py-4 text-right align-middle">
                            <div class="flex flex-col">
                                <span
                                    class="text-[10px] font-bold text-slate-400 uppercase leading-none mb-1">Total</span>
                                <span class="text-sm font-bold text-slate-900"
                                    x-text="estimate.currency_symbol + ' ' + calculateItemTotal(item).toFixed(2)"></span>
                            </div>
                        </td>
                        <!-- Actions -->
                        <td class="px-3 py-4 text-right">
                            <div class="flex flex-col items-center gap-3">
                                <button type="button" @click="removeItem(sectionIndex, itemIndex)"
                                    class="group/del h-8 w-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-all border border-transparent hover:border-rose-100">
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </button>

                                <!-- Internal Note Icon -->
                                <button type="button" @click="openInternalNoteModal(item)"
                                    class="h-8 w-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-amber-500 hover:bg-amber-50 transition-all border border-transparent hover:border-amber-100"
                                    title="Add Internal Note">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                        </tr>
                    </template>
                    </tbody>
                    </table>
                </div>

                <div x-show="section.items.length === 0 && section.section_type !== 'package'"
                    class="text-center mx-4 mb-4 mt-2 py-6 border-2 border-dashed border-slate-200 rounded-lg">
                    <p class="text-sm text-slate-500">No items in this section.</p>
                    <button type="button" @click.stop="openProductPicker(sectionIndex)"
                        class="mt-2 text-sm font-medium text-indigo-600 hover:text-indigo-500">Add first
                        item</button>
                </div>

                <div x-show="section.items.length > 0 && section.section_type !== 'package'"
                    class="p-4 bg-white flex justify-center border-t border-slate-100">
                    <button type="button" @click.stop="openProductPicker(sectionIndex)"
                        class="w-full py-3 border-2 border-dashed border-slate-300 rounded-xl text-slate-400 hover:border-indigo-500 hover:text-indigo-600 hover:bg-indigo-50 transition-all font-medium flex items-center justify-center gap-2">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path
                                d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                        </svg>
                        <span x-text="'Add Item to ' + (section.name || 'Room')"></span>
                    </button>
                </div>

                <!-- PACKAGE LAYOUT TABLE (Simplified) -->
                <div class="overflow-x-auto min-h-[100px]" x-show="section.section_type === 'package'">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-indigo-50/20">
                            <tr>
                                <th scope="col" class="w-10 py-4 px-3"></th>
                                <!-- Simplified Columns -->
                                <th scope="col"
                                    class="px-3 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                    Item Details</th>
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
                        <tbody class="bg-white divide-y divide-slate-200 section-items-sortable"
                            :data-section-index="sectionIndex">
                            <template x-for="(item, itemIndex) in section.items"
                                :key="item._uid || item.id || itemIndex">
                                <tr class="group hover:bg-slate-50/50 transition-all duration-200">
                                    <td
                                        class="px-3 py-4 text-center text-slate-300 group-hover:text-slate-400 cursor-move handle">
                                        <svg class="h-4 w-4 mx-auto" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 8h16M4 16h16" />
                                        </svg>
                                    </td>
                                    <!-- Details (Simplified for package) -->
                                    <td class="px-3 py-4">
                                        <input type="text" x-model="item.name" placeholder="Item Name" :readonly="isItemLocked(item)"
                                            :class="isItemLocked(item) ? 'text-slate-500 cursor-default' : 'text-slate-900'"
                                            class="block w-full border-0 p-0 text-sm font-bold bg-transparent mb-1">
                                        <input type="text" x-model="item.description" placeholder="Description" :readonly="isItemLocked(item)"
                                            :class="isItemLocked(item) ? 'text-slate-500 cursor-default' : 'text-slate-900'"
                                            class="block w-full border-0 p-0 text-xs bg-transparent">
                                    </td>
                                    <!-- Price -->
                                    <td class="px-3 py-4">
                                        <div class="relative">
                                            <span
                                                class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs font-medium"
                                                x-text="estimate.currency_symbol"></span>
                                            <input type="number" step="0.01" x-model="item.unit_price"
                                                @input="calculateTotals"
                                                class="block w-full rounded-lg border-slate-200 bg-slate-50/50 py-1.5 pl-8 pr-3 text-sm text-slate-900 text-right font-medium focus:ring-2 focus:ring-indigo-600 transition-all">
                                        </div>
                                    </td>
                                    <!-- Quantity -->
                                    <td class="px-3 py-4"><input type="number" step="0.01" x-model="item.quantity"
                                            @input="calculateTotals"
                                            class="block w-full rounded-lg border-slate-200 py-1.5 text-center text-sm font-bold text-slate-900 focus:ring-2 focus:ring-indigo-600 transition-all">
                                    </td>
                                    <!-- Total -->
                                    <td class="px-3 py-4 text-right align-middle">
                                        <div class="flex flex-col">
                                            <span
                                                class="text-[10px] font-bold text-slate-400 uppercase leading-none mb-1">Total</span>
                                            <span class="text-sm font-bold text-slate-900"
                                                x-text="estimate.currency_symbol + ' ' + calculateItemTotal(item).toFixed(2)"></span>
                                        </div>
                                    </td>
                                    <!-- Actions (Subset) -->
                                    <td class="px-3 py-4 text-right">
                                        <div class="flex flex-col items-center gap-3">
                                            <button type="button" @click="removeItem(sectionIndex, itemIndex)"
                                                class="group/del h-8 w-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-all border border-transparent hover:border-rose-100">
                                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd"
                                                        d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <div x-show="section.items.length === 0"
                        class="text-center py-6 mx-4 mb-4 mt-2 border-2 border-dashed border-slate-200 rounded-lg">
                        <p class="text-sm text-slate-500">No items.</p>
                    </div>
                </div>

                <!-- Section Footer -->
                <div class="bg-slate-50 border-t border-slate-200 px-4 py-3 sm:px-6 flex justify-end">
                    <div class="text-sm text-slate-600">
                        Room Total: <span class="font-bold text-slate-900"
                            x-text="calculateSectionTotal(section).toFixed(2)"></span>
                    </div>
                </div>

            </div>

            </template>


    </div>

    <!-- Standard List Table -->
    <div x-show="estimate.type === 'standard' || (estimate.type === 'room_based' && estimate.items.length > 0)"
        class="overflow-x-auto min-h-[100px]">
        <div x-show="estimate.type === 'room_based'" class="px-4 py-2 bg-slate-100/50 border-b border-slate-200">
            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">General Items</h3>
        </div>
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
                        Unit Config</th>
                    <th scope="col"
                        class="px-3 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest w-80">
                        Item Details</th>
                    <th scope="col"
                        class="px-3 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest w-36">
                        Dimensions</th>
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
                <template x-for="(item, itemIndex) in estimate.items" :key="item._uid || item.id || itemIndex">
                    <tr class="item-row group hover:bg-slate-50/50 transition-all duration-200">
                        <td class="px-3 py-4 text-center text-slate-300 group-hover:text-slate-400 cursor-move handle">
                            <svg class="h-4 w-4 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 8h16M4 16h16" />
                            </svg>
                        </td>
                        <td class="px-3 py-4 align-middle">
                            <template x-if="!item.is_package">
                                <div>
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
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </td>
                        <!-- Column 3: Unit Configuration -->
                        <td class="px-3 py-4">
                            <div class="flex flex-col gap-2" x-show="!item.is_package">
                                <div class="relative group">
                                    <select :value="item.unit_type_id ? String(item.unit_type_id) : ''"
                                         @change="item.unit_type_id = $event.target.value; onUnitTypeChange(item)"
                                        class="block w-full rounded-lg border-slate-200 bg-white py-1.5 px-2 text-[10px] font-bold text-slate-600 focus:ring-2 focus:ring-indigo-600 transition-all appearance-none cursor-pointer hover:bg-slate-50 shadow-sm">
                                        <option value="">Manual Unit Type</option>
                                        <template x-for="type in unitTypes" :key="'std_'+type.id">
                                            <option :value="String(type.id)" x-text="type.name" :selected="item.unit_type_id && String(item.unit_type_id) === String(type.id)"></option>
                                        </template>
                                    </select>
                                    <div
                                        class="absolute inset-y-0 right-2 flex items-center pointer-events-none text-slate-400">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="relative">
                                    <template x-if="item.unit_type_id">
                                        <select x-model="item.unit_type"
                                            class="block w-full rounded-lg border-indigo-200 bg-indigo-50/30 py-1 px-2 text-[10px] font-bold text-indigo-700 focus:ring-2 focus:ring-indigo-600 transition-all appearance-none cursor-pointer hover:bg-indigo-50/50 shadow-sm text-center">
                                            <template x-for="u in getUnitsByTypeId(item.unit_type_id)" :key="u">
                                                <option :value="String(u)" x-text="u"></option>
                                            </template>
                                        </select>
                                    </template>
                                    <template x-if="!item.unit_type_id">
                                        <input type="text" x-model="item.unit_type" placeholder="Unit (e.g. nos)"
                                            class="block w-full rounded-lg border-slate-200 py-1 px-2 text-[10px] font-bold text-slate-700 text-center focus:ring-2 focus:ring-indigo-600 shadow-sm">
                                    </template>
                                </div>
                            </div>
                        </td>
                        <!-- Column 3: Options (Category/Variants) -->
                        <!-- Column 4: Item Details -->
                        <td class="px-3 py-4">
                            <input type="text" x-model="item.name" placeholder="Item Name"
                                :readonly="isItemLocked(item)"
                                :class="isItemLocked(item) ? 'cursor-default text-slate-500' : 'text-slate-900'"
                                class="block w-full border-0 p-0 text-sm font-bold focus:ring-0 placeholder:text-slate-400 bg-transparent mb-1">
                            <textarea x-model="item.description" placeholder="Description" rows="2"
                                :readonly="isItemLocked(item)"
                                :class="isItemLocked(item) ? 'cursor-default text-slate-500' : 'text-slate-900'"
                                class="block w-full border-0 p-0 text-xs focus:ring-0 placeholder:text-slate-400 bg-transparent resize-none leading-relaxed"></textarea>

                            <!-- Variant Dropdowns placed exactly below description -->
                            <template
                                x-if="item.product_id && getProduct(item.product_id) && getProduct(item.product_id).options && getProduct(item.product_id).options.length > 0">
                                <div class="flex flex-wrap gap-x-4 gap-y-2 mt-2">
                                    <template x-for="option in getProduct(item.product_id).options" :key="option.id">
                                        <div class="flex flex-col gap-0.5 min-w-[120px]">
                                            <span
                                                class="text-[9px] font-bold text-slate-400 uppercase tracking-widest pl-1"
                                                x-text="option.name"></span>
                                            <div class="relative group/opt">
                                                <select @change="updateItemOption(item, option.id, $event.target.value)"
                                                    class="block w-full rounded-lg border-indigo-100 bg-white py-1 px-3 text-[11px] font-bold text-indigo-700 focus:ring-2 focus:ring-indigo-600 focus:border-indigo-600 transition-all appearance-none cursor-pointer hover:bg-indigo-50/30 shadow-sm pr-8">
                                                    <template x-for="val in option.values" :key="val.id">
                                                        <option :value="val.id"
                                                            :selected="item.selected_options[option.id] == val.id"
                                                            x-text="val.value"></option>
                                                    </template>
                                                </select>
                                                <div
                                                    class="absolute inset-y-0 right-2 flex items-center pointer-events-none text-indigo-400 group-hover/opt:text-indigo-600">
                                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <!-- Fallback Badge for Custom/Manual Options -->
                            <template
                                x-if="(!item.product_id || !getProduct(item.product_id)?.options?.length) && item.options && item.options.length > 0">
                                <div class="flex flex-wrap gap-1.5 mt-2">
                                    <template x-for="opt in item.options">
                                        <span
                                            class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600 ring-1 ring-inset ring-slate-200"
                                            x-text="opt.name + ': ' + opt.value"></span>
                                    </template>
                                </div>
                            </template>
                        </td>
                        <!-- Column 5: Dimensions -->
                        <td class="px-3 py-4">
                            <div class="flex flex-col gap-2" x-show="!item.is_package">
                                <div
                                    class="flex items-center gap-2 bg-slate-50/80 p-2 rounded-lg border border-slate-200 shadow-inner">
                                    <div class="flex flex-col gap-1.5">
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] font-bold text-slate-600 uppercase w-3">L</span>
                                            <input type="number" step="0.01" x-model="item.length" placeholder="0"
                                                @input="calculateSize(item)"
                                                class="block w-14 rounded border-slate-200 py-1 px-1.5 text-center text-xs text-slate-900 focus:ring-indigo-600">
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] font-bold text-slate-600 uppercase w-3">W</span>
                                            <input type="number" step="0.01" x-model="item.width" placeholder="0"
                                                @input="calculateSize(item)"
                                                class="block w-14 rounded border-slate-200 py-1 px-1.5 text-center text-xs text-slate-900 focus:ring-indigo-600">
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] font-bold text-slate-600 uppercase w-3">H</span>
                                            <input type="number" step="0.01" x-model="item.height" placeholder="0"
                                                @input="calculateSize(item)"
                                                class="block w-14 rounded border-slate-200 py-1 px-1.5 text-center text-xs text-slate-900 focus:ring-indigo-600">
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-center flex-1 border-l border-slate-200 pl-2">
                                        <span class="text-[9px] font-bold text-slate-400 mb-0.5 tracking-tight"
                                            x-text="item.formula === 'volume' ? 'VOLUME' : (item.formula === 'area_lh' ? 'AREA' : (item.formula === 'formula' ? 'CUSTOM' : 'AREA'))"></span>
                                        <span class="text-xs font-black text-indigo-600 leading-none"
                                            x-text="parseFloat(item.size || 0).toFixed(2)"></span>
                                        <span class="text-[8px] font-bold text-slate-400 mt-0.5"
                                            x-text="item.unit_type || 'sqft'"></span>
                                    </div>
                                </div>

                                <!-- Unit Selection -->
                                <div class="relative group">
                                    <select :value="item.unit_type_id ? String(item.unit_type_id) : ''" @change="item.unit_type_id = $event.target.value; onUnitTypeChange(item)"
                                        class="block w-full rounded-lg border-slate-200 bg-white py-1.5 px-2 text-[10px] font-bold text-slate-600 focus:ring-2 focus:ring-indigo-600 transition-all appearance-none cursor-pointer hover:bg-slate-50 shadow-sm">
                                        <option value="">Manual Unit Type</option>
                                        <template x-for="type in unitTypes" :key="'dim_'+type.id">
                                            <option :value="String(type.id)" x-text="type.name" :selected="item.unit_type_id && String(item.unit_type_id) === String(type.id)"></option>
                                        </template>
                                    </select>
                                    <div
                                        class="absolute inset-y-0 right-2 flex items-center pointer-events-none text-slate-400">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-4">
                            <div class="relative">
                                <span
                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs font-medium"
                                    x-text="estimate.currency_symbol"></span>
                                <input type="number" step="0.01" x-model="item.unit_price"
                                    :readonly="isItemLocked(item)" @input="calculateTotals"
                                    :class="isItemLocked(item) ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'bg-slate-50/50 text-slate-900 focus:ring-2 focus:ring-indigo-600'"
                                    class="block w-full rounded-lg border-slate-200 py-1.5 pl-8 pr-3 text-sm text-right font-medium transition-all">
                            </div>
                        </td>
                        <td class="px-3 py-4">
                            <div class="flex items-center justify-center gap-1.5" x-show="!item.is_package">
                                <input type="number" step="0.01" x-model="item.quantity" @input="calculateTotals"
                                    class="block w-20 rounded-lg border-slate-200 py-1.5 text-center text-sm font-bold text-slate-900 focus:ring-2 focus:ring-indigo-600 transition-all">
                            </div>
                        </td>
                        <td class="px-3 py-4 text-right vertical-align-middle">
                            <div class="flex flex-col">
                                <span
                                    class="text-[10px] font-bold text-slate-400 uppercase leading-none mb-1">Total</span>
                                <span class="text-sm font-bold text-slate-900"
                                    x-text="estimate.currency_symbol + ' ' + calculateItemTotal(item).toFixed(2)"></span>
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

                                <!-- Internal Note Icon -->
                                <button type="button" @click="openInternalNoteModal(item)"
                                    class="h-8 w-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-amber-500 hover:bg-amber-50 transition-all border border-transparent hover:border-amber-100"
                                    title="Add Internal Note">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
        <button type="button" @click.stop="openProductPicker(null)" x-show="estimate.type === 'standard'"
            class="h-full min-h-[160px] border-2 border-dashed border-slate-300 rounded-xl text-slate-400 hover:border-indigo-500 hover:text-indigo-600 hover:bg-indigo-50 transition-all font-medium flex flex-col items-center justify-center gap-2">
            <svg class="h-8 w-8" viewBox="0 0 20 20" fill="currentColor">
                <path
                    d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
            </svg>
            <span>Add Item</span>
        </button>

        <button type="button" @click="openRoomModal()" x-show="estimate.type === 'room_based'"
            class="h-full min-h-[160px] border-2 border-dashed border-slate-300 rounded-xl text-slate-400 hover:border-indigo-500 hover:text-indigo-600 hover:bg-indigo-50 transition-all font-medium flex flex-col items-center justify-center gap-2">
            <svg class="h-8 w-8" viewBox="0 0 20 20" fill="currentColor">
                <path
                    d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
            </svg>
            <span>Add New Room</span>
        </button>

        <button type="button" @click="fetchHistory()" x-show="estimate.type === 'room_based'"
            class="h-full min-h-[160px] border-2 border-dashed border-rose-300 rounded-xl text-rose-400 hover:border-rose-500 hover:text-rose-600 hover:bg-rose-50 transition-all font-medium flex flex-col items-center justify-center gap-2">
            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Recently Deleted</span>
        </button>
    </div>
    </div>

    <!-- Product Picker Modal -->
    <x-estimates.product-picker />

    <!-- Internal Note Modal -->
    <x-estimates.internal-note-modal />

    <!-- Product Configuration Modal -->
    <x-estimates.config-modal />

    <!-- Room Template Modal -->
    <x-estimates.room-modal />

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
            <div x-data="{ 
                        showGlobalTerms: false,
                        standardTerms: `<div class='space-y-8'>
    <!-- Price Promise -->
    <div class='bg-slate-50 p-5 rounded-lg border border-slate-100'>
        <h3 class='text-sm font-bold text-slate-900 uppercase tracking-widest mb-2'>Price Promise</h3>
        <p class='text-slate-600 leading-relaxed text-justify mb-0'>C2D-Concept2Designs will keep the price promise given in this proposal till the period of 60 days. The prices may vary if booked after the period. The price quotation given here is only for this project and subject to change with any modification with respect to actual measurement, brand, and model of hardware selected, finishes selected, and design selection as per the client's requirement.</p>
    </div>

    <!-- 1. Payment Terms -->
    <div>
        <h3 class='text-sm font-bold text-slate-900 uppercase tracking-widest mb-3 border-b border-slate-200 pb-2'>1. Payment Terms</h3>
        <ul class='list-none space-y-2 pl-0 my-0'>
            <li class='flex items-baseline gap-3 text-slate-600'>
                <span class='font-black text-slate-900 w-12 shrink-0 text-right'>10%</span>
                <span>Booking amount</span>
            </li>
            <li class='flex items-baseline gap-3 text-slate-600'>
                <span class='font-black text-slate-900 w-12 shrink-0 text-right'>50%</span>
                <span>To start 2D drawings and production phase.</span>
            </li>
            <li class='flex items-baseline gap-3 text-slate-600'>
                <span class='font-black text-slate-900 w-12 shrink-0 text-right'>30%</span>
                <span>2 days prior material dispatch to the site. 10% PDC to be issued on the date of material dispatch.</span>
            </li>
            <li class='flex items-baseline gap-3 text-slate-600'>
                <span class='font-black text-slate-900 w-12 shrink-0 text-right'>10%</span>
                <span>PDC will be realised from bank before Handover of the Project. Upon 100% realisation of payment, project shall be handed over.</span>
            </li>
        </ul>
    </div>

    <!-- 2. Financial Services -->
    <div>
        <h3 class='text-sm font-bold text-slate-900 uppercase tracking-widest mb-2 border-b border-slate-200 pb-2'>2. Financial Services</h3>
        <p class='text-slate-600 leading-relaxed text-justify'>C2D-Concept2Designs is proud to associate with third party service providers for extended financial services to their clients and will only provide the availability of financial ideas and services. The final approval, services, processing, structure, duration, terms, and conditions of the loan are at the sole discretion of the loan provider / financial institution / service provider.</p>
    </div>

    <!-- 3. Payment Delay -->
    <div>
        <h3 class='text-sm font-bold text-slate-900 uppercase tracking-widest mb-2 border-b border-slate-200 pb-2'>3. Payment Delay</h3>
        <p class='text-slate-600 leading-relaxed mb-4 text-justify'>Any delay in the payment by over 7 days at any phase of the project will add simultaneous days to the timeline initially decided for the delivery.</p>
        <p class='text-slate-600 font-medium mb-2'>The Delay of payment at the time when the goods are ready to dispatch from the factory may result in the following delays:</p>
        <ul class='list-disc pl-5 space-y-2 text-slate-600 marker:text-slate-400'>
            <li class='pl-1 text-justify'>The dispatch date will be recalculated after the release of the payment, and this calculation depends on the next available schedule of the dispatch. (The delay in dispatch is not equivalent to the number of days the payment was delayed and this may be more based on the next dispatch available from the production warehouse.) As dispatch date changes, new execution timelines are applied.</li>
            <li class='pl-1 text-justify'>If the delay in the payment exceeds the limit of 7 days, then the rent of the load holding shall be applicable which is 1500/- per day. It will be added to the final payment.</li>
        </ul>
    </div>

    <!-- Other Terms -->
    <div>
        <h3 class='text-sm font-bold text-slate-900 uppercase tracking-widest mb-2 border-b border-slate-200 pb-2'>Other Terms & Conditions</h3>
        <div class='text-slate-600 space-y-3 leading-relaxed text-justify'>
            <p>The client is liable to pay full at the revision immediately in case there is a revision in the estimated project cost and as per the previously agreed signed design documents.</p>
            <p>The client is solely responsible for any damage that happens to the products during the load holding phase if:</p>
            <ul class='list-disc pl-5 space-y-1 marker:text-slate-400'>
                <li class='pl-1'>The client didn't take the handover of the products within the month from the date of readiness notification.</li>
                <li class='pl-1'>The client was unable to pay the next payment slot.</li>
                <li class='pl-1'>The client fails to pay the load holding charges in full.</li>
            </ul>
            <p class='font-bold text-slate-800 pt-2'>All payments made by the client at any stage are non refundable at any stage whatsoever.</p>
        </div>
    </div>
</div>`
                    }">
                <div class="flex justify-between items-center mb-1">
                    <label class="block text-xs font-medium uppercase tracking-wide text-slate-500">Terms &
                        Conditions</label>
                    <button type="button" @click="estimate.terms = standardTerms"
                        class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold cursor-pointer transition-colors bg-indigo-50 px-2.5 py-1 rounded-md">
                        Load Standard Terms
                    </button>
                </div>
                <textarea x-model="estimate.terms" name="terms" rows="12"
                    class="block w-full rounded-md border-0 py-2 px-3 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-xs font-mono leading-relaxed"
                    placeholder="Special terms for this estimate..."></textarea>
                <div class="mt-2 flex flex-col gap-1">
                    <p class="text-[10px] text-slate-500">
                        <span class="font-bold text-indigo-600">Tip:</span> Leave empty to use Global Terms. Add
                        text to <strong>append</strong>. Start with <code
                            class="bg-slate-100 px-1 rounded text-rose-600">REPLACE:</code> to fully override.
                    </p>
                    <button type="button" @click="showGlobalTerms = !showGlobalTerms"
                        class="text-[10px] text-indigo-500 font-bold hover:underline w-fit">
                        <span x-text="showGlobalTerms ? 'Hide Global Terms' : 'View Global Terms Reference'"></span>
                    </button>
                    <div x-show="showGlobalTerms" x-cloak
                        class="mt-1 p-3 bg-slate-50 border border-slate-200 rounded-lg text-[11px] text-slate-600 whitespace-pre-wrap max-h-40 overflow-y-auto shadow-inner">
                        <div class="font-bold mb-1 uppercase tracking-wider text-[9px] text-slate-400">Current
                            Global Terms:</div>
                        {!! nl2br(htmlspecialchars($settings['estimate_terms'] ?? 'No global terms configured.')) !!}
                    </div>
                </div>
            </div>
            <div>
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
                                <span class="text-xs font-bold text-emerald-700" x-text="appliedCouponCode"></span>
                                <button type="button" @click="removeCoupon()"
                                    class="text-emerald-400 hover:text-emerald-600">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
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
                    <dt class="text-slate-600">Transportation</dt>
                    <dd class="flex items-center gap-2">
                        <span class="text-xs text-slate-400" x-text="estimate.currency_symbol"></span>
                        <input type="number" step="0.01" x-model="estimate.transportation_charges"
                            name="transportation_charges" @input="calculateTotals"
                            class="block w-20 rounded-md border-0 py-1 text-right text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 sm:text-sm sm:leading-6">
                    </dd>
                </div>

                <div class="flex justify-between items-center py-2 border-t border-slate-100 mt-2">
                    <dt class="text-slate-600 font-semibold uppercase text-[10px] tracking-wider">GST</dt>
                    <dd class="flex items-center gap-4">
                        <div class="flex flex-col items-end">
                            <label class="text-[10px] text-slate-400 mb-0.5">Rate (%)</label>
                            <input type="number" step="0.01" x-model="estimate.tax_1" name="tax_1"
                                @input="calculateTotals"
                                class="block w-20 rounded-md border-0 py-1 text-right text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 sm:text-sm sm:leading-6">
                        </div>
                    </dd>
                </div>
                <div class="flex justify-between border-t border-slate-200 pt-3">
                    <dt class="text-base font-bold text-slate-900">Grand Total</dt>
                    <dd class="text-base font-bold text-indigo-600"
                        x-text="estimate.currency_symbol + ' ' + totals.grandTotal.toFixed(2)"></dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Sticky Save Bar -->
    <div
        class="fixed bottom-0 left-0 right-0 bg-white/90 backdrop-blur-md border-t border-slate-200 p-4 sm:px-8 z-50 flex justify-end items-center gap-3 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">

        <button type="button" @click="showDeletedModal = true"
            id="restore-button-sticky"
            class="inline-flex items-center gap-x-1.5 rounded-md bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-700 shadow-sm ring-1 ring-inset ring-amber-200 hover:bg-amber-100 transition-all mr-auto">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            Restore Deleted Items (<span x-text="deletedItems.length + deletedSections.length"></span>)
        </button>

        <div class="h-6 w-px bg-slate-200 mx-2"></div>
        <button type="button" @click="window.history.back()"
            class="text-sm font-semibold leading-6 text-slate-600 hover:text-slate-900 transition-colors px-4">Cancel</button>

        <button type="button" @click="saveAjax()"
            class="inline-flex items-center gap-x-2 rounded-md bg-white px-5 py-2.5 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition-all"
            x-bind:disabled="isSubmitting"
            x-bind:class="{ 'opacity-75 cursor-not-allowed': isSubmitting }">
            <div class="flex items-center">
                <div x-show="isSubmitting" class="mr-2" style="display: none;">
                    <x-loading-spinner size="4" />
                </div>
                <span x-text="isSubmitting ? 'Saving...' : 'Save'"></span>
            </div>
        </button>
        <button type="button" @click="submitForm(true)"
            class="inline-flex items-center gap-x-2 rounded-md bg-white px-5 py-2.5 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition-all"
            x-bind:disabled="isSubmitting" x-show="estimate.id"
            x-bind:class="{ 'opacity-75 cursor-not-allowed': isSubmitting }">
            <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                <path
                    d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
            </svg>
            Save as New Version
        </button>

        <x-primary-button type="button" @click="submitForm(false)" class="px-8 py-2.5" x-bind:disabled="isSubmitting"
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
    <!-- Modal container start -->

    <!-- Restoration Modal -->
    <div x-show="showDeletedModal" class="relative z-[60]" x-cloak>
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div @click.away="showDeletedModal = false"
                    class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-2xl">
                    <div class="bg-white px-6 pb-6 pt-5 sm:p-8 sm:pb-6">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900">Restore Deleted Items</h3>
                                    <p class="text-sm text-slate-500">Recover items or rooms that were removed during this session.</p>
                                </div>
                            </div>
                            <button @click="showDeletedModal = false" class="text-slate-400 hover:text-slate-500 transition-colors">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="space-y-6 max-h-[60vh] overflow-y-auto pr-2 custom-scrollbar">
                            <!-- Rooms -->
                            <div x-show="deletedSections.length > 0">
                                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Recently Deleted Rooms</h4>
                                <div class="space-y-2">
                                    <template x-for="(section, index) in deletedSections" :key="'del_sec_'+index">
                                        <div class="flex items-center justify-between p-3 rounded-xl border border-slate-200 bg-slate-50 hover:bg-white hover:border-indigo-300 transition-all group">
                                            <div class="flex items-center gap-3">
                                                <div class="h-8 w-8 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-600">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <span class="text-sm font-bold text-slate-900" x-text="section.name"></span>
                                                    <span class="text-[10px] text-slate-400 block" x-text="(section.items ? section.items.length : 0) + ' items'"></span>
                                                </div>
                                            </div>
                                            <button @click="restoreSection(index)" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-600 text-[11px] font-bold text-white hover:bg-indigo-700 shadow-sm transition-all">
                                                Restore
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- Items -->
                            <div x-show="deletedItems.length > 0">
                                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Recently Deleted Items</h4>
                                <div class="space-y-2">
                                    <template x-for="(item, index) in deletedItems" :key="'del_item_'+index">
                                        <div class="flex items-center justify-between p-3 rounded-xl border border-slate-200 bg-slate-50 hover:bg-white hover:border-indigo-300 transition-all group">
                                            <div class="flex items-center gap-3">
                                                <template x-if="item.image_url">
                                                    <img :src="item.image_url" class="h-10 w-10 rounded-lg object-cover ring-1 ring-slate-200 shadow-sm">
                                                </template>
                                                <template x-if="!item.image_url">
                                                    <div class="h-10 w-10 rounded-lg bg-slate-200 flex items-center justify-center text-slate-400 ring-1 ring-slate-200">
                                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                        </svg>
                                                    </div>
                                                </template>
                                                <div>
                                                    <span class="text-sm font-bold text-slate-900" x-text="item.name"></span>
                                                    <span class="text-[10px] text-slate-400 block" x-text="item._sectionName ? 'From: ' + item._sectionName : 'Standalone Item'"></span>
                                                </div>
                                            </div>
                                            <button @click="restoreItem(index)" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-600 text-[11px] font-bold text-white hover:bg-indigo-700 shadow-sm transition-all">
                                                Restore
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div x-show="deletedItems.length === 0 && deletedSections.length === 0" class="text-center py-12">
                                <div class="h-16 w-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="h-8 w-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </div>
                                <p class="text-slate-500 font-medium">No deleted items to restore.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

        <!-- History Recovery Modal -->
        <div x-show="historyModal.isOpen" class="fixed inset-0 z-[60] overflow-y-auto" x-cloak>
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div x-show="historyModal.isOpen" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-2xl px-6 pt-5 pb-6">
                    
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-rose-100 rounded-lg">
                                <svg class="h-6 w-6 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-slate-900">Restoration History</h3>
                                <p class="text-sm text-slate-500">Recover rooms and items that were recently removed.</p>
                            </div>
                        </div>
                        <button type="button" @click="historyModal.isOpen = false" class="text-slate-400 hover:text-slate-500">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="mt-4">
                        <div x-show="historyModal.isLoading" class="flex justify-center py-10">
                            <svg class="animate-spin h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </div>

                        <div x-show="!historyModal.isLoading && historyModal.data.length === 0" class="text-center py-10">
                            <p class="text-slate-500">No recent deletions found for this estimate.</p>
                        </div>

                        <div x-show="!historyModal.isLoading && historyModal.data.length > 0" class="space-y-4 max-h-[60vh] overflow-y-auto pr-2">
                            <template x-for="(session, index) in historyModal.data" :key="index">
                                <div class="p-4 border border-slate-200 rounded-xl hover:border-indigo-200 transition-colors">
                                    <div class="flex justify-between items-start mb-3">
                                        <div>
                                            <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600 mb-1" x-text="session.label" x-show="session.label"></span>
                                            <div class="text-sm font-bold text-slate-900" x-text="session.time_format"></div>
                                        </div>
                                        <button type="button" @click="restoreSession(session.timestamp)"
                                            class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-bold text-white shadow-sm hover:bg-indigo-500 transition-all">
                                            Restore All
                                        </button>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex gap-2 text-xs" x-show="session.room_count > 0">
                                            <span class="font-bold text-slate-500 text-[10px] uppercase">Rooms:</span>
                                            <span class="text-slate-700 font-medium" x-text="session.rooms.join(', ')"></span>
                                        </div>
                                        <div class="flex gap-2 text-xs" x-show="session.item_count > 0">
                                            <span class="font-bold text-slate-500 text-[10px] uppercase">Items:</span>
                                            <span class="text-slate-700 font-medium tracking-tight" x-text="session.item_preview + (session.item_count > 5 ? '...' : '')"></span>
                                            <span class="text-slate-400 font-medium" x-text="'(' + session.item_count + ' total)'"></span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
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
    </div>
</x-app-layout>
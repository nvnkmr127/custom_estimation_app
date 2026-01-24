<x-app-layout>
    <div class="max-w-[95%] mx-auto px-4 sm:px-6 lg:px-8 py-10"
        x-data='templateForm({{ $products->toJson() }}, {{ isset($template) && $template->items ? json_encode($template->items) : "[]" }}, {{ $unitTypes->toJson() }})'>

        <!-- Header -->
        <div
            class="mb-12 flex flex-col md:flex-row md:items-end md:justify-between gap-6 pb-6 border-b border-slate-200">
            <div class="space-y-2">
                <nav class="flex mb-4" aria-label="Breadcrumb">
                    <ol role="list" class="flex items-center space-x-2">
                        <li>
                            <a href="{{ route('templates.index') }}"
                                class="text-[11px] font-bold uppercase tracking-[0.1em] text-slate-400 hover:text-slate-800 transition-colors">Templates</a>
                        </li>
                        <li class="flex items-center space-x-2">
                            <svg class="h-3 w-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="3">
                                <path d="M9 5l7 7-7 7" />
                            </svg>
                            <span
                                class="text-[11px] font-black uppercase tracking-[0.1em] text-slate-950">{{ isset($template) ? 'Edit' : 'Create' }}</span>
                        </li>
                    </ol>
                </nav>
                <h1 class="text-4xl font-black text-slate-950 tracking-tightest">
                    {{ isset($template) ? 'Edit Template' : 'New Room Template' }}
                </h1>
                <p class="text-slate-600 text-lg font-bold max-w-2xl leading-relaxed">Customize your room
                    configuration with preset products, default quantities, and specific unit types.</p>
            </div>

            <div class="flex items-center gap-4">
                <a href="{{ route('templates.index') }}"
                    class="inline-flex items-center px-6 py-3 rounded-2xl bg-white text-slate-600 text-sm font-bold shadow-sm border border-slate-200 hover:bg-slate-50 transition-all active:scale-95">
                    Cancel
                </a>
                <button type="submit" form="template-form"
                    class="inline-flex items-center px-10 py-3 rounded-2xl bg-slate-950 text-white text-sm font-black shadow-xl shadow-slate-200 hover:bg-black transition-all active:scale-95 group">
                    <svg class="mr-2 h-5 w-5 text-indigo-300 group-hover:rotate-6 transition-transform" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    Save Template
                </button>
            </div>
        </div>

        <form id="template-form"
            action="{{ isset($template) ? route('templates.update', $template) : route('templates.store') }}"
            method="POST" class="space-y-10">
            @csrf
            @if(isset($template))
                @method('PUT')
            @endif

            <!-- Top Configuration Bar (Horizontally Scrollable) -->
            <div class="flex overflow-x-auto gap-8 pb-10 scrollbar-hide snap-x snap-mandatory">
                <!-- General Information -->
                <div
                    class="min-w-[400px] snap-start bg-white rounded-[2rem] p-8 shadow-sm border border-slate-200 overflow-hidden relative flex-shrink-0">
                    <h2
                        class="text-[11px] font-black text-slate-950 uppercase tracking-[0.2em] mb-6 pb-3 border-b border-slate-100 flex items-center">
                        <span class="mr-3 h-2 w-2 rounded-full bg-slate-950"></span>
                        Template Details
                    </h2>
                    <div class="space-y-6">
                        <div>
                            <label for="name"
                                class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Room
                                Name</label>
                            <input type="text" name="name" id="name" required
                                value="{{ old('name', $template->name ?? '') }}" placeholder="e.g. Master Bedroom"
                                class="block w-full rounded-2xl border-slate-200 bg-slate-50/50 py-3 px-4 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all text-sm font-semibold text-slate-800">
                        </div>
                        <div>
                            <label for="description"
                                class="block text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-2">Notes</label>
                            <textarea name="description" id="description" rows="2" placeholder="Brief notes..."
                                class="block w-full rounded-2xl border-slate-200 bg-slate-50/50 py-3 px-4 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all text-sm font-medium text-slate-600">{{ old('description', $template->description ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Unit Configuration -->
                <div
                    class="min-w-[450px] snap-start bg-white rounded-[2rem] p-8 shadow-sm border border-slate-200 flex-shrink-0">
                    <div class="flex items-center justify-between mb-6 pb-3 border-b border-slate-100">
                        <h2 class="text-[11px] font-bold text-slate-500 uppercase tracking-[0.2em] flex items-center">
                            <span
                                class="mr-3 h-2 w-2 rounded-full bg-emerald-500 shadow-lg shadow-emerald-200 animate-pulse"></span>
                            Allowed Unit Types
                        </h2>
                        <template x-if="allowedUnitTypes.length > 0">
                            <button type="button" @click="allowedUnitTypes = []"
                                class="text-[10px] font-bold text-rose-500 uppercase tracking-widest hover:text-rose-700 transition-colors">Clear</button>
                        </template>
                    </div>
                    <div class="flex gap-4 overflow-x-auto pb-2 scrollbar-hide">
                        <template x-for="unitType in unitTypes" :key="unitType.id">
                            <label
                                class="flex-shrink-0 flex flex-col items-center p-5 rounded-[1.75rem] border-2 border-slate-50 bg-slate-50/30 hover:bg-white hover:border-indigo-400 hover:shadow-xl hover:shadow-indigo-50 transition-all cursor-pointer group w-36 relative overflow-hidden"
                                :class="allowedUnitTypes.includes(String(unitType.id)) ? 'ring-2 ring-indigo-600 border-transparent bg-indigo-50/30 shadow-lg shadow-indigo-100' : ''">
                                <input type="checkbox" :value="String(unitType.id)" x-model="allowedUnitTypes"
                                    class="sr-only">
                                <div class="absolute top-2 right-2 transition-opacity"
                                    :class="allowedUnitTypes.includes(String(unitType.id)) ? 'opacity-100' : 'opacity-0'">
                                    <svg class="h-4 w-4 text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" />
                                    </svg>
                                </div>
                                <span
                                    class="text-xs font-black text-slate-950 group-hover:text-indigo-600 transition-colors text-center uppercase tracking-tight"
                                    x-text="unitType.name"></span>
                                <span
                                    class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter mt-2 bg-white px-2 py-0.5 rounded shadow-sm border border-slate-100"
                                    x-text="unitType.units[0]"></span>
                            </label>
                        </template>
                    </div>
                    <!-- Hidden inputs for form submission -->
                    <template x-for="(typeId, index) in allowedUnitTypes" :key="'allowed-'+index">
                        <input type="hidden" :name="`allowed_unit_types[${index}]`" :value="typeId">
                    </template>
                </div>

                <!-- Editor Stats -->
                <div
                    class="min-w-[300px] snap-start bg-slate-950 rounded-[2rem] p-8 shadow-xl shadow-slate-200/50 relative overflow-hidden text-white flex-shrink-0 group">
                    <div
                        class="absolute top-0 right-0 p-6 opacity-20 group-hover:scale-125 transition-transform duration-500">
                        <svg class="h-20 w-20" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M11 15h2v2h-2zm0-8h2v6h-2zm1-5C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z" />
                        </svg>
                    </div>
                    <div class="relative z-10">
                        <div class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-500 mb-1">Items
                            Configured</div>
                        <div class="text-6xl font-black tabular-nums tracking-tighter" x-text="items.length">0</div>
                        <p class="text-slate-400 text-[11px] font-bold mt-4 leading-tight italic">"Ready to expedite
                            your workflow."</p>
                    </div>
                </div>
            </div>

            <!-- Main Items Editor -->
            <div class="space-y-10">
                <div
                    class="bg-white rounded-[2rem] shadow-xl shadow-slate-200/50 border border-slate-200 overflow-hidden">
                    <div
                        class="px-10 py-10 border-b border-slate-100 flex flex-wrap items-center justify-between gap-6 bg-slate-50/20 backdrop-blur-sm">
                        <div class="flex items-center gap-5">
                            <div
                                class="h-14 w-14 rounded-2xl bg-slate-950 flex items-center justify-center text-white shadow-lg shadow-indigo-100">
                                <svg class="h-8 w-8 text-indigo-100" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-2xl font-black text-slate-950 tracking-tight">Room Items</h3>
                                <p class="mt-0.5 text-sm text-slate-600 font-bold">Build your template selection
                                    by adding items below.</p>
                            </div>
                        </div>
                        <button type="button" @click="openProductPicker()"
                            class="inline-flex items-center px-8 py-4 rounded-2xl bg-slate-950 text-white text-sm font-black shadow-xl shadow-slate-200 hover:bg-black transition-all active:scale-95 group">
                            <svg class="mr-2 h-6 w-6 text-indigo-400 group-hover:rotate-90 transition-transform duration-300"
                                viewBox="0 0 20 20" fill="currentColor">
                                <path
                                    d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                            </svg>
                            Browse Library
                        </button>
                    </div>

                    <!-- Items Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100">
                            <thead>
                                <tr class="bg-slate-900 border-b-4 border-white font-bold">
                                    <th
                                        class="px-10 py-6 text-left text-[11px] text-white/50 uppercase tracking-widest w-20">
                                        Preview</th>
                                    <th class="px-6 py-6 text-left text-[11px] text-white/50 uppercase tracking-widest">
                                        Product Details</th>
                                    <th
                                        class="px-6 py-6 text-center text-[10px] font-bold text-white/50 uppercase tracking-[0.2em] w-24">
                                        Quantity</th>
                                    <th
                                        class="px-6 py-6 text-center text-[10px] font-bold text-white/50 uppercase tracking-[0.2em] w-64">
                                        Dimensions</th>
                                    <th
                                        class="px-6 py-6 text-center text-[10px] font-bold text-white/50 uppercase tracking-[0.2em] w-52">
                                        Unit Type</th>
                                    <th
                                        class="px-6 py-6 text-right text-[10px] font-bold text-white/50 uppercase tracking-[0.2em] w-40">
                                        Price Config</th>
                                    <th
                                        class="px-10 py-6 text-right text-[11px] text-white/50 uppercase tracking-widest w-16">
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <template x-for="(item, index) in isReady ? items : []" :key="'item-'+index">
                                    <tr
                                        class="group hover:bg-slate-50/80 transition-all border-b border-transparent hover:border-slate-100">
                                        <!-- Preview -->
                                        <td class="px-8 py-6">
                                            <div
                                                class="h-14 w-14 rounded-2xl bg-slate-100 ring-1 ring-slate-200/50 overflow-hidden flex items-center justify-center group-hover:scale-110 transition-transform shadow-sm">
                                                <template x-if="getProductImage(item.product_id)">
                                                    <img :src="getProductImage(item.product_id)"
                                                        class="h-full w-full object-cover">
                                                </template>
                                                <template x-if="!getProductImage(item.product_id)">
                                                    <svg class="h-6 w-6 text-slate-300" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor" stroke-width="1.5">
                                                        <path
                                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                </template>
                                            </div>
                                        </td>

                                        <td class="px-6 py-8">
                                            <input type="hidden" :name="`items[${index}][product_id]`"
                                                :value="item.product_id">
                                            <div class="flex flex-col space-y-2 relative group-item">
                                                <input type="text" :name="`items[${index}][item_name]`"
                                                    x-model="item.item_name" required
                                                    class="block w-full border-0 p-0 text-lg font-black text-slate-950 placeholder:text-slate-300 focus:ring-0 bg-transparent hover:text-indigo-600 transition-colors tracking-tight"
                                                    placeholder="Item Name">

                                                <div class="flex items-center gap-2">
                                                    <input type="text" :name="`items[${index}][description]`"
                                                        x-model="item.description" :title="item.description"
                                                        class="block flex-1 border-0 p-0 text-sm text-slate-600 font-bold placeholder:text-slate-200 focus:ring-0 bg-transparent leading-relaxed truncate"
                                                        placeholder="Add specifications or notes...">
                                                    <button type="button" @click="viewDescription(item)"
                                                        x-show="item.description"
                                                        class="p-1.5 text-slate-300 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all">
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                            stroke="currentColor" stroke-width="2.5">
                                                            <path
                                                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Configuration Tags -->
                                            <template x-if="item.options && item.options.length > 0">
                                                <div class="flex flex-wrap gap-1.5 mt-4">
                                                    <template x-for="(opt, optIndex) in item.options"
                                                        :key="'opt-'+optIndex">
                                                        <div
                                                            class="inline-flex items-center px-2 py-0.5 rounded-lg bg-indigo-50 text-[9px] font-bold text-indigo-500 border border-indigo-100/50 uppercase tracking-tighter">
                                                            <span x-text="opt.name + ': ' + opt.value"></span>
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
                                        <td class="px-6 py-10">
                                            <div class="flex flex-col items-center">
                                                <div
                                                    class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-2">
                                                    Quantity</div>
                                                <input type="number" step="0.01" :name="`items[${index}][quantity]`"
                                                    x-model="item.quantity"
                                                    class="block w-20 rounded-2xl border-2 border-slate-100 bg-slate-50/50 py-3 text-center text-lg font-black text-slate-950 focus:ring-2 focus:ring-slate-950 focus:bg-white focus:border-slate-950 transition-all shadow-inner">
                                            </div>
                                        </td>
                                        <!-- Dimensions -->
                                        <td class="px-6 py-10 leading-none">
                                            <div
                                                class="bg-slate-50/50 border-2 border-slate-100 rounded-[1.5rem] p-4 flex flex-col gap-4 group-hover:bg-white transition-colors shadow-inner">
                                                <div class="flex items-center gap-3">
                                                    <div class="flex-1">
                                                        <div
                                                            class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-2 px-1">
                                                            L <span class="text-slate-300 font-normal">(ft)</span></div>
                                                        <input type="number" step="0.01"
                                                            :name="`items[${index}][length]`" x-model="item.length"
                                                            class="block w-full rounded-xl border-slate-200 bg-white py-2 text-center text-sm font-semibold text-slate-800 focus:ring-2 focus:ring-indigo-500 transition-all shadow-sm">
                                                    </div>
                                                    <span class="text-slate-300 text-xs mt-6 font-bold">×</span>
                                                    <div class="flex-1">
                                                        <div
                                                            class="text-[9px] font-bold text-slate-500 uppercase mb-2 px-1">
                                                            W <span class="text-slate-300">(ft)</span></div>
                                                        <input type="number" step="0.01"
                                                            :name="`items[${index}][width]`" x-model="item.width"
                                                            class="block w-full rounded-xl border-slate-200 bg-white py-2 text-center text-sm font-black text-slate-950 focus:ring-2 focus:ring-slate-900 transition-all shadow-sm">
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-3">
                                                    <div class="flex-1">
                                                        <div
                                                            class="text-[9px] font-bold text-slate-500 uppercase mb-2 px-1">
                                                            H <span class="text-slate-300">(ft)</span></div>
                                                        <input type="number" step="0.01"
                                                            :name="`items[${index}][height]`" x-model="item.height"
                                                            class="block w-full rounded-xl border-slate-200 bg-white py-2 text-center text-sm font-semibold text-slate-800 focus:ring-2 focus:ring-indigo-500 transition-all shadow-sm">
                                                    </div>
                                                    <div class="flex-1 flex items-center justify-center pt-5">
                                                        <template x-if="item.length && item.width">
                                                            <div
                                                                class="text-[11px] font-bold text-indigo-600 bg-indigo-50 px-3 py-1.5 rounded-xl border-2 border-indigo-100 uppercase tracking-wider shadow-sm">
                                                                <span
                                                                    x-text="(item.length * item.width).toFixed(2)"></span>
                                                                <span
                                                                    class="text-[9px] opacity-60 font-medium">Sqft</span>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Unit Configuration -->
                                        <td class="px-6 py-10">
                                            <div class="flex flex-col gap-4">
                                                <div class="relative group/sel">
                                                    <div class="relative group/sel">
                                                        <div
                                                            class="text-[9px] font-black text-slate-400 uppercase mb-2 px-1">
                                                            Source</div>
                                                        <select x-model="item.unit_type_id"
                                                            @change="onUnitTypeChange(item)"
                                                            class="block w-full rounded-2xl border-2 border-slate-100 bg-slate-50/50 py-3 px-4 text-[10px] font-black text-slate-950 uppercase tracking-widest focus:ring-2 focus:ring-slate-950 focus:bg-white focus:border-slate-950 transition-all appearance-none cursor-pointer hover:shadow-sm">
                                                            <option value="">MANUAL UNIT</option>
                                                            <template x-for="ut in getFilteredUnitTypes()"
                                                                :key="'ut-'+ut.id">
                                                                <option :value="String(ut.id)" x-text="ut.name">
                                                                </option>
                                                            </template>
                                                        </select>
                                                        <div
                                                            class="absolute bottom-4 right-4 flex items-center pointer-events-none text-slate-400">
                                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24"
                                                                stroke="currentColor" stroke-width="4">
                                                                <path d="M19 9l-7 7-7-7" />
                                                            </svg>
                                                        </div>
                                                    </div>

                                                    <div class="relative">
                                                        <div
                                                            class="text-[9px] font-bold text-slate-400 uppercase mb-2 px-1">
                                                            Unit</div>
                                                        <template x-if="item.unit_type_id">
                                                            <div class="relative">
                                                                <select x-model="item.unit_type"
                                                                    :name="`items[${index}][unit_type]`"
                                                                    class="block w-full rounded-2xl border-2 border-indigo-100 bg-white py-3 px-4 text-[13px] font-bold text-indigo-600 focus:ring-2 focus:ring-indigo-500 transition-all appearance-none cursor-pointer text-center shadow-md">
                                                                    <template
                                                                        x-for="u in getAvailableUnits(item.unit_type_id)"
                                                                        :key="'u-'+u">
                                                                        <option :value="u" x-text="u"></option>
                                                                    </template>
                                                                </select>
                                                                <div
                                                                    class="absolute bottom-4 right-4 flex items-center pointer-events-none text-indigo-300">
                                                                    <svg class="h-2.5 w-2.5" fill="none"
                                                                        viewBox="0 0 24 24" stroke="currentColor"
                                                                        stroke-width="5">
                                                                        <path d="M19 9l-7 7-7-7" />
                                                                    </svg>
                                                                </div>
                                                            </div>
                                                        </template>
                                                        <template x-if="!item.unit_type_id">
                                                            <input type="text" x-model="item.unit_type"
                                                                :name="`items[${index}][unit_type]`"
                                                                placeholder="e.g. nos"
                                                                class="block w-full rounded-2xl border-2 border-slate-100 bg-white py-3 px-4 text-[13px] font-semibold text-slate-700 text-center focus:ring-2 focus:ring-indigo-500 shadow-sm placeholder:text-slate-200">
                                                        </template>
                                                    </div>
                                                </div>
                                        </td>

                                        <!-- Unit Price -->
                                        <td class="px-6 py-10 text-right">
                                            <div
                                                class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-2">
                                                Unit Price
                                            </div>
                                            <div class="relative inline-block w-40">
                                                <span
                                                    class="absolute inset-y-0 left-5 flex items-center text-slate-600 font-bold text-lg">₹</span>
                                                <input type="number" step="0.01" :name="`items[${index}][unit_price]`"
                                                    x-model="item.unit_price"
                                                    class="block w-full rounded-2xl border-2 border-emerald-100 bg-emerald-50/10 py-4 pl-10 pr-6 text-right text-xl font-black text-slate-950 focus:ring-2 focus:ring-emerald-500 transition-all shadow-lg shadow-emerald-50/50">
                                            </div>
                                        </td>

                                        <!-- Remove Action -->
                                        <td class="px-8 py-6 text-right">
                                            <button type="button" @click="items.splice(index, 1)"
                                                class="p-2 text-slate-300 hover:text-rose-500 hover:bg-rose-50 rounded-xl transition-all active:scale-95">
                                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                                                    stroke="currentColor" stroke-width="2.5">
                                                    <path
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </template>

                                <!-- Empty State -->
                                <tr x-show="items.length === 0">
                                    <td colspan="6" class="px-8 py-20 text-center">
                                        <div class="max-w-xs mx-auto flex flex-col items-center">
                                            <div
                                                class="h-20 w-20 rounded-full bg-slate-50 flex items-center justify-center text-slate-200 mb-6 border border-slate-100">
                                                <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor" stroke-width="1.5">
                                                    <path
                                                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                </svg>
                                            </div>
                                            <h4 class="text-base font-black text-slate-950 mb-2">Build your template
                                            </h4>
                                            <p class="text-sm text-slate-500 font-medium mb-8">Start by adding
                                                products that typically go together in this type of room.</p>
                                            <button type="button" @click="openProductPicker()"
                                                class="px-8 py-3 rounded-2xl bg-slate-900 text-white text-xs font-bold uppercase tracking-widest shadow-sm hover:bg-slate-800 transition-all active:scale-95">
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
                                x-text="descriptionModal.title"></h3>
                            <button type="button" @click="descriptionModal.isOpen = false"
                                class="p-2 text-slate-300 hover:text-slate-600 hover:bg-slate-100 rounded-2xl transition-all">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2.5">
                                    <path d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="p-8">
                            <label class="block text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 mb-4">Edit Description</label>
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
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
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
                    });
                    this.isReady = true;
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
    </style>
</x-app-layout>
<x-app-layout>
    <div x-data="{}" x-init="console.log('Alpine initialized on Unit Types page')" class="min-h-screen pb-12">
        <!-- Header Section -->
        <div class="mb-10">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Unit Types</h1>
                    <p class="mt-2 text-slate-500 font-medium">Manage and organize your measurement unit types for
                        estimates.</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <button type="button"
                        @click="console.log('Dispatching open-modal add-unit-type'); $dispatch('open-modal', 'add-unit-type')"
                        class="inline-flex items-center px-4 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-bold shadow-md shadow-indigo-200 hover:bg-indigo-700 transition-all active:scale-95">
                        <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Unit Type
                    </button>
                </div>
            </div>
        </div>

        <!-- Search Bar Section (Visual Mockup to match Products) -->
        <div
            class="bg-white/60 backdrop-blur-md sticky top-4 z-30 p-4 rounded-2xl border border-slate-200 shadow-sm mb-8">
            <div class="flex flex-col lg:flex-row gap-4 items-center">
                <div class="relative flex-1 w-full">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <input type="text" placeholder="Search unit types..."
                        class="block w-full rounded-xl border-slate-200 bg-slate-50/50 py-3 pl-11 pr-4 text-slate-900 placeholder:text-slate-400 focus:ring-2 focus:ring-indigo-600 focus:border-transparent transition-all sm:text-sm">
                </div>
            </div>
        </div>

        <!-- Unit Types Table -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead>
                        <tr class="bg-slate-50/50">
                            <th
                                class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                Main Type</th>
                            <th
                                class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                Units</th>
                            <th
                                class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 bg-white">
                        @foreach($unitTypes as $type)
                            <tr class="hover:bg-slate-50/50 transition-all group">
                                <td class="px-6 py-4">
                                    <span class="text-sm font-bold text-slate-900 leading-tight">{{ $type->name }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($type->units as $unit)
                                            <span
                                                class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                                {{ $unit }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button"
                                            @click="console.log('Dispatching open-modal edit-unit-type-{{ $type->id }}'); $dispatch('open-modal', 'edit-unit-type-{{ $type->id }}')"
                                            class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <form action="{{ route('unit-types.destroy', $type) }}" method="POST" class="inline"
                                            onsubmit="return confirm('Are you sure you want to delete this unit type?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-all">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            <!-- Edit Modal -->
                            <x-modal name="edit-unit-type-{{ $type->id }}" focusable maxWidth="xl">
                                <!-- Modal Header with Gradient Accent -->
                                <div class="relative overflow-hidden">
                                    <!-- Gradient Background -->
                                    <div
                                        class="absolute inset-0 bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 opacity-60">
                                    </div>

                                    <div class="relative px-8 pt-8 pb-6 flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3 mb-2">
                                                <div
                                                    class="p-2.5 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl shadow-lg shadow-indigo-200">
                                                    <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <h3 class="text-2xl font-black text-slate-900 tracking-tight">Edit Unit
                                                        Type</h3>
                                                    <p class="text-sm text-slate-600 font-medium mt-0.5">Modify <span
                                                            class="font-bold text-indigo-600">{{ $type->name }}</span> and
                                                        manage its units</p>
                                                </div>
                                            </div>
                                        </div>
                                        <button @click="$dispatch('close')"
                                            class="p-2.5 text-slate-400 hover:text-slate-700 hover:bg-white/80 rounded-xl transition-all hover:shadow-md hover:scale-105 active:scale-95">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <form method="post" action="{{ route('unit-types.update', $type) }}"
                                    id="edit-unit-type-form-{{ $type->id }}" class="p-8">
                                    @csrf
                                    @method('PUT')

                                    <div class="space-y-8">
                                        <!-- Unit Type Name Section -->
                                        <div
                                            class="bg-gradient-to-br from-slate-50 to-slate-100/50 rounded-2xl p-6 border border-slate-200/60">
                                            <label
                                                class="flex items-center gap-2 text-xs font-black uppercase tracking-widest text-slate-500 mb-3">
                                                <svg class="h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                                </svg>
                                                Main Type Name
                                                <span class="text-rose-500">*</span>
                                            </label>
                                            <input type="text" name="name" value="{{ $type->name }}"
                                                form="edit-unit-type-form-{{ $type->id }}" required
                                                class="block w-full rounded-xl border-slate-300 bg-white py-3.5 px-4 text-slate-900 font-semibold shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all hover:border-indigo-300"
                                                placeholder="e.g., Flooring, Paint, etc.">
                                        </div>

                                        <!-- Units Management Section -->
                                        <div x-data="{ 
                                                                            units: {{ json_encode($type->units) }},
                                                                            draggedIndex: null,
                                                                            moveUp(index) {
                                                                                if (index > 0) {
                                                                                    let temp = this.units[index];
                                                                                    this.units[index] = this.units[index-1];
                                                                                    this.units[index-1] = temp;
                                                                                    this.units = [...this.units];
                                                                                }
                                                                            },
                                                                            moveDown(index) {
                                                                                if (index < this.units.length - 1) {
                                                                                    let temp = this.units[index];
                                                                                    this.units[index] = this.units[index+1];
                                                                                    this.units[index+1] = temp;
                                                                                    this.units = [...this.units];
                                                                                }
                                                                            }
                                                                        }">
                                            <div
                                                class="bg-gradient-to-br from-indigo-50/50 to-purple-50/30 rounded-2xl p-6 border border-indigo-100">
                                                <div class="flex items-center justify-between mb-5">
                                                    <label
                                                        class="flex items-center gap-2 text-xs font-black uppercase tracking-widest text-slate-500">
                                                        <svg class="h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24"
                                                            stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                                        </svg>
                                                        Units & Order
                                                        <span
                                                            class="ml-1 px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded-full text-[10px] font-bold"
                                                            x-text="units.length"></span>
                                                    </label>
                                                    <button type="button" @click="units.push('')"
                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-xs font-bold rounded-lg shadow-md shadow-indigo-200 hover:shadow-lg hover:shadow-indigo-300 transition-all hover:scale-105 active:scale-95">
                                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"
                                                            stroke="currentColor" stroke-width="3">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M12 4v16m8-8H4" />
                                                        </svg>
                                                        Add Unit
                                                    </button>
                                                </div>

                                                <!-- Units List -->
                                                <div class="space-y-3">
                                                    <template x-for="(unit, index) in units" :key="index">
                                                        <div class="group relative bg-white rounded-xl border-2 border-slate-200 hover:border-indigo-300 transition-all duration-200 hover:shadow-lg hover:shadow-indigo-100/50"
                                                            :class="{ 'ring-2 ring-indigo-400 ring-offset-2': draggedIndex === index }">
                                                            <div class="flex items-center gap-3 p-3">
                                                                <!-- Order Number Badge -->
                                                                <div
                                                                    class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-slate-100 to-slate-200 rounded-lg flex items-center justify-center">
                                                                    <span class="text-xs font-black text-slate-600"
                                                                        x-text="index + 1"></span>
                                                                </div>

                                                                <!-- Reorder Controls -->
                                                                <div class="flex flex-col gap-0.5">
                                                                    <button type="button" @click="moveUp(index)"
                                                                        :disabled="index === 0"
                                                                        class="p-1 rounded-md text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 disabled:opacity-20 disabled:hover:text-slate-400 disabled:hover:bg-transparent transition-all"
                                                                        title="Move up">
                                                                        <svg class="h-3.5 w-3.5" fill="none"
                                                                            viewBox="0 0 24 24" stroke="currentColor"
                                                                            stroke-width="3">
                                                                            <path stroke-linecap="round"
                                                                                stroke-linejoin="round" d="M5 15l7-7 7 7" />
                                                                        </svg>
                                                                    </button>
                                                                    <button type="button" @click="moveDown(index)"
                                                                        :disabled="index === units.length - 1"
                                                                        class="p-1 rounded-md text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 disabled:opacity-20 disabled:hover:text-slate-400 disabled:hover:bg-transparent transition-all"
                                                                        title="Move down">
                                                                        <svg class="h-3.5 w-3.5" fill="none"
                                                                            viewBox="0 0 24 24" stroke="currentColor"
                                                                            stroke-width="3">
                                                                            <path stroke-linecap="round"
                                                                                stroke-linejoin="round"
                                                                                d="M19 9l-7 7-7-7" />
                                                                        </svg>
                                                                    </button>
                                                                </div>

                                                                <!-- Unit Input -->
                                                                <div class="flex-1 relative">
                                                                    <input type="text" :value="units[index]"
                                                                        @input="units[index] = $event.target.value"
                                                                        name="units[]"
                                                                        :form="'edit-unit-type-form-{{ $type->id }}'"
                                                                        required
                                                                        class="block w-full rounded-lg border-0 bg-slate-50 py-2.5 px-3 text-sm font-semibold text-slate-900 placeholder:text-slate-400 focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all"
                                                                        placeholder="e.g. sqft, sqm, meter">
                                                                </div>

                                                                <!-- Delete Button -->
                                                                <button type="button" @click="units.splice(index, 1)"
                                                                    class="flex-shrink-0 p-2 text-slate-300 hover:text-rose-500 hover:bg-rose-50 rounded-lg transition-all group-hover:scale-100 scale-90"
                                                                    title="Remove unit">
                                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                                        stroke="currentColor" stroke-width="2.5">
                                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                    </svg>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </template>

                                                    <!-- Empty State -->
                                                    <div x-show="units.length === 0" class="text-center py-8">
                                                        <div
                                                            class="inline-flex items-center justify-center w-16 h-16 bg-slate-100 rounded-full mb-3">
                                                            <svg class="h-8 w-8 text-slate-400" fill="none"
                                                                viewBox="0 0 24 24" stroke="currentColor"
                                                                stroke-width="1.5">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                            </svg>
                                                        </div>
                                                        <p class="text-sm font-semibold text-slate-600">No units added yet
                                                        </p>
                                                        <p class="text-xs text-slate-500 mt-1">Click "Add Unit" to get
                                                            started</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div
                                        class="mt-8 pt-6 border-t border-slate-200 flex flex-col sm:flex-row-reverse gap-3">
                                        <button type="submit"
                                            class="inline-flex items-center justify-center gap-2 px-8 py-3.5 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-sm font-black shadow-xl shadow-indigo-200 hover:shadow-2xl hover:shadow-indigo-300 hover:scale-105 transition-all active:scale-95">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Update Unit Type
                                        </button>
                                        <button type="button" @click="$dispatch('close')"
                                            class="inline-flex items-center justify-center gap-2 px-8 py-3.5 rounded-xl bg-white border-2 border-slate-200 text-slate-700 text-sm font-black hover:bg-slate-50 hover:border-slate-300 transition-all active:scale-95">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                            Cancel
                                        </button>
                                    </div>
                                </form>
                            </x-modal>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($unitTypes->isEmpty())
                <div class="px-6 py-20 text-center bg-white">
                    <div
                        class="inline-flex h-20 w-20 items-center justify-center rounded-full bg-slate-50 text-slate-300 mb-6 border border-slate-100">
                        <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-black text-slate-900">No unit types found</h3>
                    <p class="mt-2 text-slate-500 max-w-sm mx-auto font-medium">Start by adding your first unit type (e.g.
                        Flooring with sqft, sqm).</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Add Modal -->
    <x-modal name="add-unit-type" focusable maxWidth="xl">
        <!-- Modal Header with Gradient Accent -->
        <div class="relative overflow-hidden">
            <!-- Gradient Background -->
            <div class="absolute inset-0 bg-gradient-to-br from-emerald-50 via-teal-50 to-cyan-50 opacity-60"></div>

            <div class="relative px-8 pt-8 pb-6 flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <div
                            class="p-2.5 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl shadow-lg shadow-emerald-200">
                            <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-black text-slate-900 tracking-tight">Add New Unit Type</h3>
                            <p class="text-sm text-slate-600 font-medium mt-0.5">Create a unit category and define its
                                measurement units</p>
                        </div>
                    </div>
                </div>
                <button @click="$dispatch('close')"
                    class="p-2.5 text-slate-400 hover:text-slate-700 hover:bg-white/80 rounded-xl transition-all hover:shadow-md hover:scale-105 active:scale-95">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <form method="post" action="{{ route('unit-types.store') }}" class="p-8">
            @csrf

            <div class="space-y-8">
                <!-- Unit Type Name Section -->
                <div class="bg-gradient-to-br from-slate-50 to-slate-100/50 rounded-2xl p-6 border border-slate-200/60">
                    <label
                        class="flex items-center gap-2 text-xs font-black uppercase tracking-widest text-slate-500 mb-3">
                        <svg class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        Main Type Name
                        <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="name" required
                        class="block w-full rounded-xl border-slate-300 bg-white py-3.5 px-4 text-slate-900 font-semibold shadow-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all hover:border-emerald-300"
                        placeholder="e.g., Flooring, Paint, Tiles, etc.">
                </div>

                <!-- Units Management Section -->
                <div x-data="{ units: [''] }">
                    <div
                        class="bg-gradient-to-br from-emerald-50/50 to-teal-50/30 rounded-2xl p-6 border border-emerald-100">
                        <div class="flex items-center justify-between mb-5">
                            <label
                                class="flex items-center gap-2 text-xs font-black uppercase tracking-widest text-slate-500">
                                <svg class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                </svg>
                                Measurement Units
                                <span
                                    class="ml-1 px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold"
                                    x-text="units.length"></span>
                            </label>
                            <button type="button" @click="units.push('')"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-emerald-600 to-teal-600 text-white text-xs font-bold rounded-lg shadow-md shadow-emerald-200 hover:shadow-lg hover:shadow-emerald-300 transition-all hover:scale-105 active:scale-95">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                                Add Unit
                            </button>
                        </div>

                        <!-- Units List -->
                        <div class="space-y-3">
                            <template x-for="(unit, index) in units" :key="index">
                                <div
                                    class="group relative bg-white rounded-xl border-2 border-slate-200 hover:border-emerald-300 transition-all duration-200 hover:shadow-lg hover:shadow-emerald-100/50">
                                    <div class="flex items-center gap-3 p-3">
                                        <!-- Order Number Badge -->
                                        <div
                                            class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-emerald-100 to-teal-200 rounded-lg flex items-center justify-center">
                                            <span class="text-xs font-black text-emerald-700" x-text="index + 1"></span>
                                        </div>

                                        <!-- Unit Input -->
                                        <div class="flex-1 relative">
                                            <input type="text" :value="units[index]"
                                                @input="units[index] = $event.target.value" name="units[]" required
                                                class="block w-full rounded-lg border-0 bg-slate-50 py-2.5 px-3 text-sm font-semibold text-slate-900 placeholder:text-slate-400 focus:ring-2 focus:ring-emerald-500 focus:bg-white transition-all"
                                                placeholder="e.g. sqft, sqm, meter, piece">
                                        </div>

                                        <!-- Delete Button -->
                                        <button type="button" @click="units.splice(index, 1)" x-show="units.length > 1"
                                            class="flex-shrink-0 p-2 text-slate-300 hover:text-rose-500 hover:bg-rose-50 rounded-lg transition-all group-hover:scale-100 scale-90"
                                            title="Remove unit">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <!-- Empty State -->
                            <div x-show="units.length === 0" class="text-center py-8">
                                <div
                                    class="inline-flex items-center justify-center w-16 h-16 bg-slate-100 rounded-full mb-3">
                                    <svg class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                </div>
                                <p class="text-sm font-semibold text-slate-600">No units added yet</p>
                                <p class="text-xs text-slate-500 mt-1">Click "Add Unit" to get started</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-8 pt-6 border-t border-slate-200 flex flex-col sm:flex-row-reverse gap-3">
                <button type="submit"
                    class="inline-flex items-center justify-center gap-2 px-8 py-3.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 text-white text-sm font-black shadow-xl shadow-emerald-200 hover:shadow-2xl hover:shadow-emerald-300 hover:scale-105 transition-all active:scale-95">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    Create Unit Type
                </button>
                <button type="button" @click="$dispatch('close')"
                    class="inline-flex items-center justify-center gap-2 px-8 py-3.5 rounded-xl bg-white border-2 border-slate-200 text-slate-700 text-sm font-black hover:bg-slate-50 hover:border-slate-300 transition-all active:scale-95">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Cancel
                </button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
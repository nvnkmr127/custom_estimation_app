<div x-show="roomModal.isOpen" class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true"
    style="display: none;">
    <div x-show="roomModal.isOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-500/75 transition-opacity">
    </div>

    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div x-show="roomModal.isOpen" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                @click.away="roomModal.isOpen = false"
                class="relative transform overflow-hidden rounded-xl bg-white px-4 pb-4 pt-5 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">

                <div>
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50">
                                <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900" id="modal-title">Add New Room</h3>
                                <p class="text-sm text-slate-500">Choose a template or enter a custom name.</p>
                            </div>
                        </div>
                        <button type="button" @click="roomModal.isOpen = false"
                            class="text-slate-400 hover:text-slate-500 transition-colors">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-6">
                        <!-- Option 1: Template Selection -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-2">Select from Template</label>
                            <div class="grid grid-cols-1 gap-3 max-h-48 overflow-y-auto pr-2 custom-scrollbar">
                                <template x-for="template in templates" :key="template.id">
                                    <button type="button"
                                        @click="roomModal.templateId = template.id; roomModal.name = ''; confirmRoom()"
                                        class="flex items-center justify-between w-full px-4 py-3 rounded-xl border border-slate-200 hover:border-indigo-500 hover:bg-indigo-50 transition-all group">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="h-8 w-8 rounded-lg bg-slate-100 flex items-center justify-center group-hover:bg-indigo-100">
                                                <svg class="h-4 w-4 text-slate-500 group-hover:text-indigo-600"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                </svg>
                                            </div>
                                            <span class="text-sm font-medium text-slate-700 group-hover:text-indigo-900"
                                                x-text="template.name"></span>
                                        </div>
                                        <svg class="h-5 w-5 text-slate-400 group-hover:text-indigo-600" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                </template>
                                <div x-show="templates.length === 0"
                                    class="text-center py-4 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                                    <p class="text-xs text-slate-500 font-medium">No room templates available</p>
                                </div>
                            </div>
                        </div>

                        <div class="relative">
                            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                                <div class="w-full border-t border-slate-200"></div>
                            </div>
                            <div class="relative flex justify-center text-xs uppercase tracking-widest font-bold">
                                <span class="bg-white px-2 text-slate-400">Or Manual Entry</span>
                            </div>
                        </div>

                        <!-- Option 2: Custom Name -->
                        <div>
                            <label for="room_name" class="block text-sm font-semibold text-slate-900 mb-2">Room
                                Name</label>
                            <div class="flex gap-2">
                                <input type="text" id="room_name" x-model="roomModal.name"
                                    @keydown.enter.prevent="confirmRoom()"
                                    class="block w-full rounded-lg border-slate-300 py-2.5 text-slate-900 shadow-sm focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm"
                                    placeholder="e.g. Master Bedroom, Living Room">
                                <button type="button" @click="confirmRoom()"
                                    class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-all">
                                    Add
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end gap-3 border-t border-slate-100 pt-6">
                        <button type="button" @click="roomModal.isOpen = false"
                            class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition-all">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
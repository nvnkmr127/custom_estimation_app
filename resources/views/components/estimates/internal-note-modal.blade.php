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
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
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
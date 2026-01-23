<div x-show="configModal.isOpen" class="relative z-[60]" aria-labelledby="modal-title" role="dialog" aria-modal="true"
    style="display: none;">
    <div x-show="configModal.isOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-500/75 transition-opacity">
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

                <template x-if="configModal.product">
                    <div>
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-bold text-slate-900"
                                x-text="'Configure ' + (configModal.product?.name || 'Item')"></h3>
                            <button type="button" @click="closeConfigModal()"
                                class="text-slate-400 hover:text-slate-500">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
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
                </template>

            </div>
        </div>
    </div>
</div>
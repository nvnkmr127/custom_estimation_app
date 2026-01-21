<div x-show="showMetricsModal" class="relative z-10" aria-labelledby="modal-title" role="dialog" aria-modal="true"
    style="display: none;">
    <div class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity"></div>
    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div
                class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl">
                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-semibold leading-6 text-slate-900 mb-4">Automation Performance
                        Metrics</h3>

                    <div x-show="loadingMetrics" class="py-10 text-center italic text-slate-500">Calculating
                        metrics...</div>

                    <div x-show="!loadingMetrics" class="space-y-6">
                        <div class="grid grid-cols-3 gap-4">
                            <div class="p-4 bg-blue-50 rounded-lg border border-blue-100">
                                <div class="text-[10px] font-bold text-blue-400 uppercase">Total Triggers</div>
                                <div class="mt-1 text-2xl font-bold text-blue-900" x-text="metrics.trigger_count">
                                </div>
                            </div>
                            <div class="p-4 bg-green-50 rounded-lg border border-green-100">
                                <div class="text-[10px] font-bold text-green-400 uppercase">Completion Rate
                                </div>
                                <div class="mt-1 text-2xl font-bold text-green-900"
                                    x-text="metrics.completion_rate + '%'"></div>
                            </div>
                            <div class="p-4 bg-red-50 rounded-lg border border-red-100">
                                <div class="text-[10px] font-bold text-red-400 uppercase">Failure Rate</div>
                                <div class="mt-1 text-2xl font-bold text-red-900" x-text="metrics.failure_rate + '%'">
                                </div>
                            </div>
                        </div>

                        <div class="p-4 bg-slate-50 rounded-lg border border-slate-200">
                            <h4 class="text-xs font-bold text-slate-700 uppercase mb-3">Conversion Indicators
                            </h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <div class="text-[10px] font-semibold text-slate-500 uppercase">Direct
                                        Acceptance</div>
                                    <div class="flex items-end gap-2">
                                        <div class="text-xl font-bold text-slate-900"
                                            x-text="metrics.conversion.accepted"></div>
                                        <div class="text-xs text-slate-500 mb-0.5"
                                            x-text="'out of ' + metrics.conversion.total_entities + ' entities'">
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <div class="text-[10px] font-semibold text-slate-500 uppercase">Engagement
                                        (Views)</div>
                                    <div class="flex items-end gap-2">
                                        <div class="text-xl font-bold text-slate-900"
                                            x-text="metrics.conversion.opened"></div>
                                        <div class="text-xs text-slate-500 mb-0.5"
                                            x-text="Math.round((metrics.conversion.opened / (metrics.conversion.total_entities || 1)) * 100) + '% engagement'">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <button @click="showMetricsModal = false" type="button"
                        class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
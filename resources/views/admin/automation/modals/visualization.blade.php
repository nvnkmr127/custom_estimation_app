<div x-show="showVisualizationModal" class="relative z-50" style="display: none;">
    <div class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity"></div>
    <div class="fixed inset-0 z-50 w-screen overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div
                class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-5xl">
                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold leading-6 text-slate-900">Workflow Visualization
                        </h3>
                        <div class="flex gap-2">
                            <button @click="visualizationView = 'flowchart'"
                                :class="visualizationView === 'flowchart' ? 'bg-indigo-600 text-white' : 'bg-white text-slate-700 ring-1 ring-inset ring-slate-300'"
                                class="px-3 py-1.5 text-xs font-semibold rounded-md transition-colors">
                                Flowchart
                            </button>
                            <button x-show="!isPreviewMode" @click="visualizationView = 'timeline'"
                                :class="visualizationView === 'timeline' ? 'bg-indigo-600 text-white' : 'bg-white text-slate-700 ring-1 ring-inset ring-slate-300'"
                                class="px-3 py-1.5 text-xs font-semibold rounded-md transition-colors">
                                Timeline
                            </button>
                        </div>
                    </div>

                    <div x-show="loadingVisualization" class="py-12 text-center">
                        <div
                            class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-indigo-600 border-r-transparent">
                        </div>
                        <p class="mt-2 text-sm text-slate-500">Loading visualization...</p>
                    </div>

                    <!-- Flowchart View -->
                    <!-- Flowchart View -->
                    <div x-show="visualizationView === 'flowchart'" class="mt-4">
                        <div class="bg-slate-50 rounded-lg p-6 overflow-auto" style="max-height: 600px;">
                            <div id="automation-mermaid-diagram" class="mermaid text-center"></div>
                        </div>
                    </div>

                    <!-- Timeline View -->
                    <div x-show="!loadingVisualization && visualizationView === 'timeline'" class="mt-4">
                        <div class="bg-slate-50 rounded-lg p-6 overflow-auto" style="max-height: 600px;">
                            <div class="space-y-4">
                                <template x-for="(item, index) in timelineData" :key="index">
                                    <div class="flex gap-4 items-start">
                                        <div class="flex flex-col items-center">
                                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-xs font-semibold"
                                                :class="item.type === 'trigger' ? 'bg-green-100 text-green-700' : 'bg-indigo-100 text-indigo-700'">
                                                <span
                                                    x-text="item.time === 0 ? '0s' : (item.time < 60 ? item.time + 's' : (item.time < 3600 ? Math.round(item.time/60) + 'm' : Math.round(item.time/3600) + 'h'))"></span>
                                            </div>
                                            <div x-show="index < timelineData.length - 1"
                                                class="w-0.5 h-12 bg-slate-300 mt-2"></div>
                                        </div>
                                        <div class="flex-1 pb-8">
                                            <div class="bg-white rounded-lg p-4 shadow-sm ring-1 ring-slate-200">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <span class="font-semibold text-sm text-slate-900"
                                                        x-text="item.label"></span>
                                                    <span x-show="item.type === 'step' && !item.is_enabled"
                                                        class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">Disabled</span>
                                                </div>
                                                <p class="text-xs text-slate-600" x-text="item.description">
                                                </p>
                                                <div x-show="item.has_conditions"
                                                    class="mt-2 text-xs text-amber-600 flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                    </svg>
                                                    <span>Has conditions</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <button @click="showVisualizationModal = false" type="button"
                        class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
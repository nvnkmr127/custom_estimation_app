<div x-show="showTemplateModal" class="relative z-10" aria-labelledby="modal-title" role="dialog" aria-modal="true"
    style="display: none;">
    <div x-show="showTemplateModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity">
    </div>

    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div x-show="showTemplateModal" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-4xl">

                <!-- Header -->
                <div class="bg-indigo-600 px-4 py-3 sm:px-6 flex justify-between items-center">
                    <h3 class="text-base font-semibold leading-6 text-white" id="modal-title">Automation
                        Templates</h3>
                    <button @click="showTemplateModal = false" class="text-indigo-200 hover:text-white">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <!-- Filter Tabs & Import -->
                    <div class="mb-6 border-b border-slate-200 flex justify-between items-center">
                        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                            <button @click="templateTab = 'all'"
                                :class="templateTab === 'all' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                                class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium">All
                                Templates</button>
                            <button @click="templateTab = 'system'"
                                :class="templateTab === 'system' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                                class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium">System</button>
                            <button @click="templateTab = 'user'"
                                :class="templateTab === 'user' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                                class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium">My
                                Templates</button>
                        </nav>
                        <div class="pb-2">
                            <input type="file" x-ref="importInput" class="hidden" accept=".json"
                                @change="handleImport($event)">
                            <button @click="$refs.importInput.click()"
                                class="text-sm text-indigo-600 hover:text-indigo-900 font-medium flex items-center gap-1 px-3 py-1 rounded hover:bg-indigo-50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12">
                                    </path>
                                </svg>
                                Import
                            </button>
                        </div>
                    </div>

                    <div x-show="loadingTemplates" class="py-12 text-center">
                        <svg class="animate-spin -ml-1 mr-3 h-8 w-8 text-indigo-500 mx-auto mb-3"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        <span class="text-sm text-slate-500">Loading templates library...</span>
                    </div>

                    <!-- Templates Grid -->
                    <div x-show="!loadingTemplates"
                        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 max-h-[60vh] overflow-y-auto p-1">
                        <template x-for="template in filteredTemplates" :key="template.id">
                            <div
                                class="relative flex flex-col rounded-lg border border-slate-200 bg-white p-4 shadow-sm hover:shadow-md transition-shadow">
                                <div class="flex-1">
                                    <div class="flex justify-between items-start mb-2">
                                        <span x-show="template.is_system"
                                            class="inline-flex items-center rounded-md bg-purple-50 px-2 py-1 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-700/10">System</span>
                                        <span x-show="!template.is_system"
                                            class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">Custom</span>
                                        <span class="text-xs text-slate-400"
                                            x-text="template.category || 'Uncategorized'"></span>
                                    </div>
                                    <h4 class="text-sm font-bold text-slate-900 mb-1" x-text="template.name">
                                    </h4>
                                    <p class="text-xs text-slate-500 line-clamp-3 mb-4"
                                        x-text="template.description || 'No description provided.'"></p>
                                </div>
                                <div class="mt-4 pt-4 border-t border-slate-100 flex justify-between items-center">
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="confirmDeleteTemplate(template)"
                                            x-show="!template.is_system"
                                            class="text-xs text-red-500 hover:text-red-700">Delete</button>
                                        <button type="button" @click="exportTemplate(template)"
                                            x-show="!template.is_system"
                                            class="text-xs text-blue-500 hover:text-blue-700">Export</button>
                                    </div>
                                    <div class="flex items-center gap-2 ml-auto">
                                        <button type="button" @click="previewTemplate(template)"
                                            class="text-xs text-slate-500 hover:text-indigo-600 font-medium px-2">Preview</button>
                                        <button type="button" @click="installTemplate(template)"
                                            class="inline-flex items-center rounded-md bg-indigo-50 px-2.5 py-1.5 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10 hover:bg-indigo-100">
                                            Use Template
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <!-- Empty State -->
                        <div x-show="filteredTemplates.length === 0"
                            class="col-span-full py-10 text-center text-slate-500 italic">
                            No templates found in this category.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
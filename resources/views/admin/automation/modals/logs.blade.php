<div x-show="showLogsModal" class="relative z-10" aria-labelledby="modal-title" role="dialog" aria-modal="true"
    style="display: none;">
    <div class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity"></div>
    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div
                class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-4xl">
                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-semibold leading-6 text-slate-900 mb-4">Automation Execution Traces
                    </h3>
                    <p class="text-xs text-slate-500 mb-4 italic">Grouped by individual trigger events. Showing
                        latest traces.</p>

                    <div class="mb-4">
                        <input type="text" x-model="logSearch" placeholder="Search by Event ID, status, or type..."
                            class="block w-full rounded-md border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                    </div>

                    <div class="max-h-[60vh] overflow-y-auto pr-2 custom-scrollbar">
                        <div x-show="loadingLogs" class="py-10 text-center italic text-slate-500">Loading traces...
                        </div>

                        <div x-show="!loadingLogs" class="space-y-3">
                            <template x-for="(trace, eventId) in filteredLogs" :key="eventId">
                                <div class="border border-slate-200 rounded-lg overflow-hidden bg-white shadow-sm">
                                    <!-- Accordion Header -->
                                    <div @click="expandedTrace === eventId ? expandedTrace = null : expandedTrace = eventId"
                                        class="px-4 py-3 flex items-center justify-between cursor-pointer hover:bg-slate-50 transition-colors select-none">
                                        <div class="flex items-center gap-3">
                                            <div :class="expandedTrace === eventId ? 'rotate-90' : ''"
                                                class="transition-transform duration-200 text-slate-400">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                                    fill="currentColor" class="w-5 h-5">
                                                    <path fill-rule="evenodd"
                                                        d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-xs font-mono text-slate-500"
                                                    x-text="'#' + eventId.substring(0,8)"></span>
                                                <span class="text-xs font-semibold text-slate-700"
                                                    x-text="new Date(trace[0].created_at).toLocaleString()"></span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="text-[10px] text-slate-400"
                                                x-text="trace.length + ' steps'"></span>
                                            <span
                                                :class="trace.every(l => l.status === 'success') ? 'bg-green-100 text-green-700 ring-green-600/20' : 'bg-red-100 text-red-700 ring-red-600/20'"
                                                class="text-[10px] font-bold px-2 py-1 rounded ring-1 ring-inset uppercase tracking-wide"
                                                x-text="trace.every(l => l.status === 'success') ? 'Success' : 'Failed'">
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Accordion Body -->
                                    <div x-show="expandedTrace === eventId" x-collapse
                                        class="border-t border-slate-200 bg-slate-50/50">
                                        <div class="p-3 space-y-2">
                                            <template x-for="(log, index) in trace" :key="log.id">
                                                <div class="bg-white border boundary-l-4 rounded shadow-sm p-3 relative overflow-hidden"
                                                    :class="{
                                                        'border-l-green-500': log.status === 'success',
                                                        'border-l-red-500': log.status === 'failed',
                                                        'border-l-blue-500': log.status === 'pending'
                                                    }">

                                                    <div class="flex justify-between items-start mb-2">
                                                        <div>
                                                            <div class="flex items-center gap-2">
                                                                <span class="text-sm font-semibold text-slate-900"
                                                                    x-text="log.action_type.toUpperCase()"></span>
                                                                <span
                                                                    class="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-500 border border-slate-200"
                                                                    x-text="log.step?.order !== undefined ? 'Step ' + (parseInt(log.step.order) + 1) : 'Global Condition'"></span>
                                                            </div>
                                                            <div class="text-[10px] text-slate-400 mt-1"
                                                                x-text="new Date(log.created_at).toLocaleString()">
                                                            </div>
                                                        </div>
                                                        <span :class="{
                                                                'text-green-600 bg-green-50': log.status === 'success',
                                                                'text-red-600 bg-red-50': log.status === 'failed',
                                                                'text-blue-600 bg-blue-50': log.status === 'pending'
                                                            }"
                                                            class="text-[10px] px-2 py-1 rounded font-medium uppercase"
                                                            x-text="log.status">
                                                        </span>
                                                    </div>

                                                    <div x-show="log.error"
                                                        class="mb-2 p-2 bg-red-50 text-red-700 text-xs rounded border border-red-100">
                                                        <p class="font-bold">Error:</p>
                                                        <p x-text="log.error"></p>
                                                    </div>

                                                    <div x-data="{ showPayload: false }">
                                                        <button @click="showPayload = !showPayload"
                                                            class="text-[10px] text-indigo-600 hover:text-indigo-800 flex items-center gap-1 font-medium">
                                                            <span
                                                                x-text="showPayload ? 'Hide Details' : 'View Details'"></span>
                                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                                                fill="currentColor" class="w-3 h-3"
                                                                :class="showPayload ? 'rotate-180' : ''">
                                                                <path fill-rule="evenodd"
                                                                    d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                                                                    clip-rule="evenodd" />
                                                            </svg>
                                                        </button>
                                                        <div x-show="showPayload" x-collapse class="mt-2">
                                                            <div
                                                                class="text-[10px] font-mono bg-slate-900 text-slate-200 p-2 rounded overflow-x-auto">
                                                                <pre
                                                                    x-text="JSON.stringify(JSON.parse(log.payload || '{}'), null, 2)"></pre>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <template x-if="Object.keys(filteredLogs).length === 0">
                                <div class="py-12 text-center text-slate-400 italic">
                                    <span x-show="!logSearch">No execution traces found.</span>
                                    <span x-show="logSearch">No traces match your search.</span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <button type="button" @click="showLogsModal = false"
                        class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
<div x-show="showCreateModal" class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true"
    style="display: none;">
    <div class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity"></div>
    <div class="fixed inset-0 z-50 w-screen overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div
                class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-3xl">
                <form action="{{ route('automation.store') }}" method="POST">
                    @csrf
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <h3 class="text-base font-semibold leading-6 text-slate-900 mb-5" id="modal-title">Create
                            New Automation Rule</h3>
                        <div class="space-y-6">
                            <div class="grid grid-cols-2 gap-5">
                                <div class="col-span-2 sm:col-span-1">
                                    <label
                                        class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Rule
                                        Name</label>
                                    <input type="text" name="name" required
                                        placeholder="e.g. Welcome Email for New Users"
                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                </div>
                                <div class="col-span-2 sm:col-span-1">
                                    <label
                                        class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Trigger
                                        Event</label>
                                    <select name="trigger_event"
                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                        @foreach ($events as $event)
                                            <option value="{{ $event }}">{{ $event }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Description -->
                            <div>
                                <label
                                    class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Description
                                    (Optional)</label>
                                <textarea name="description" rows="2"
                                    placeholder="Brief description of what this automation does..."
                                    class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"></textarea>
                            </div>

                            <!-- Global Conditions -->
                            <div class="p-4 bg-slate-50 rounded-lg ring-1 ring-slate-900/5"
                                x-data="{ conditionsExpanded: true }">
                                <div class="flex items-center justify-between mb-3">
                                    <button type="button" @click="conditionsExpanded = !conditionsExpanded"
                                        class="flex items-center gap-2 text-xs font-bold text-slate-500 uppercase tracking-wider hover:text-slate-700 transition-colors">
                                        <svg class="h-4 w-4 transition-transform duration-200"
                                            :class="conditionsExpanded ? 'rotate-90' : ''" fill="none"
                                            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                        </svg>
                                        Global Entry Conditions
                                    </button>
                                    <button type="button" @click="addCondition(editingRule)"
                                        class="text-xs font-semibold text-indigo-600 hover:text-indigo-500 hover:underline">+
                                        Add Condition</button>
                                </div>
                                <div x-show="conditionsExpanded" x-collapse class="space-y-2">
                                    <template x-for="(condition, cIndex) in editingRule.conditions" :key="cIndex">
                                        <div
                                            class="grid grid-cols-12 gap-3 p-2.5 bg-white rounded-md shadow-sm ring-1 ring-slate-900/5 relative items-end">
                                            <div class="col-span-3">
                                                <label
                                                    class="text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1 block">Type</label>
                                                <select :name="'conditions[' + cIndex + '][type]'"
                                                    x-model="condition.type"
                                                    class="block w-full rounded-md border-0 py-1 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-xs sm:leading-5">
                                                    <option value="payload">Payload</option>
                                                    <option value="entity">Entity</option>
                                                    <option value="counts">Counts</option>
                                                </select>
                                            </div>
                                            <div class="col-span-3">
                                                <label
                                                    class="text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1 block"
                                                    x-text="condition.type === 'counts' ? 'Event' : 'Field'"></label>
                                                <input type="text" :name="'conditions[' + cIndex + '][field]'"
                                                    x-model="condition.field" placeholder="e.g. status"
                                                    class="block w-full rounded-md border-0 py-1 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-xs sm:leading-5">
                                            </div>
                                            <div class="col-span-2">
                                                <label
                                                    class="text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1 block">Op</label>
                                                <select :name="'conditions[' + cIndex + '][operator]'"
                                                    x-model="condition.operator"
                                                    class="block w-full rounded-md border-0 py-1 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-xs sm:leading-5">
                                                    <option value="=">=</option>
                                                    <option value=">">&gt;</option>
                                                    <option value="<">&lt;</option>
                                                    <option value=">=">&gt;=</option>
                                                    <option value="<=">&lt;=</option>
                                                    <option value="!=">!=</option>
                                                </select>
                                            </div>
                                            <div class="col-span-3">
                                                <label
                                                    class="text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1 block">Value</label>
                                                <input type="text" :name="'conditions[' + cIndex + '][value]'"
                                                    x-model="condition.value" placeholder="value"
                                                    class="block w-full rounded-md border-0 py-1 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-xs sm:leading-5">
                                            </div>
                                            <div class="col-span-1 flex justify-center pb-1">
                                                <button type="button" @click="removeCondition(editingRule, cIndex)"
                                                    class="p-1 text-slate-400 hover:text-red-500 transition-colors">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                        stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                    <div x-show="!editingRule.conditions || editingRule.conditions.length === 0"
                                        class="text-center py-4 text-xs text-slate-400 italic">
                                        No entry conditions defined. This rule will trigger for all matching events.
                                    </div>
                                </div>
                            </div>

                            <!-- Workflow Steps -->
                            <div class="p-4 bg-slate-50 rounded-lg ring-1 ring-slate-900/5"
                                x-data="{ stepsExpanded: true }">
                                <div class="flex items-center justify-between mb-3">
                                    <button type="button" @click="stepsExpanded = !stepsExpanded"
                                        class="flex items-center gap-2 text-xs font-bold text-slate-500 uppercase tracking-wider hover:text-slate-700 transition-colors">
                                        <svg class="h-4 w-4 transition-transform duration-200"
                                            :class="stepsExpanded ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24"
                                            stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                        </svg>
                                        Workflow Steps
                                    </button>
                                    <button type="button" @click="addAction(editingRule)"
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-500">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4v16m8-8H4" />
                                        </svg>
                                        Add Step
                                    </button>
                                </div>
                                <div x-show="stepsExpanded" x-collapse class="space-y-3">
                                    <template x-for="(action, index) in editingRule.actions" :key="index">
                                        <div class="p-4 bg-white rounded-lg shadow-sm ring-1 ring-slate-900/5 relative">

                                            <!-- Step Header -->
                                            <div
                                                class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
                                                <div class="flex items-center gap-3">
                                                    <!-- Step Number Badge -->
                                                    <span
                                                        class="flex items-center justify-center w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold"
                                                        x-text="'#' + (index + 1)"></span>
                                                    <span class="text-xs font-semibold text-slate-700">Step <span
                                                            x-text="index + 1"></span></span>

                                                    <!-- Enable/Disable Toggle -->
                                                    <label class="inline-flex items-center cursor-pointer"
                                                        title="Enable/Disable Step">
                                                        <input type="checkbox"
                                                            :name="'actions[' + index + '][is_enabled]'"
                                                            x-model="action.is_enabled"
                                                            :checked="action.is_enabled !== false" value="1"
                                                            class="sr-only peer">
                                                        <div
                                                            class="relative w-9 h-5 bg-slate-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600">
                                                        </div>
                                                        <span class="ms-2 text-[10px] font-medium text-slate-500"
                                                            x-text="action.is_enabled !== false ? 'Enabled' : 'Disabled'"></span>
                                                    </label>
                                                </div>

                                                <div class="flex items-center gap-1">
                                                    <!-- Reorder Buttons -->
                                                    <button type="button" @click="moveActionUp(editingRule, index)"
                                                        x-show="index > 0"
                                                        class="p-1 text-slate-400 hover:text-indigo-600 transition-colors"
                                                        title="Move Up">
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                            stroke-width="2" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                                        </svg>
                                                    </button>
                                                    <button type="button" @click="moveActionDown(editingRule, index)"
                                                        x-show="index < editingRule.actions.length - 1"
                                                        class="p-1 text-slate-400 hover:text-indigo-600 transition-colors"
                                                        title="Move Down">
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                            stroke-width="2" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                                        </svg>
                                                    </button>

                                                    <!-- Duplicate Button -->
                                                    <button type="button" @click="duplicateAction(editingRule, index)"
                                                        class="p-1 text-slate-400 hover:text-cyan-600 transition-colors"
                                                        title="Duplicate Step">
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                            stroke-width="2" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75" />
                                                        </svg>
                                                    </button>

                                                    <!-- Remove Button -->
                                                    <button type="button" @click="removeAction(editingRule, index)"
                                                        class="p-1 text-slate-400 hover:text-red-500 transition-colors"
                                                        title="Remove Step">
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                            stroke-width="2" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Step Description -->
                                            <div class="mb-3">
                                                <label
                                                    class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1">Step
                                                    Description (Optional)</label>
                                                <input type="text" :name="'actions[' + index + '][description]'"
                                                    x-model="action.description" placeholder="What does this step do?"
                                                    class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-xs sm:leading-5">
                                            </div>

                                            <div class="grid grid-cols-6 gap-4">
                                                <div class="col-span-2">
                                                    <label
                                                        class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1">Type</label>
                                                    <select :name="'actions[' + index + '][type]'" x-model="action.type"
                                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-xs sm:leading-5">
                                                        <option value="email">Send Email</option>
                                                        <option value="webhook">POST Webhook</option>
                                                        <option value="notification">Internal Notification</option>
                                                        <option value="status_update">Update Status/Field</option>
                                                    </select>
                                                </div>
                                                <div x-show="action.type === 'email'" class="col-span-4">
                                                    <label
                                                        class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1">To
                                                        / Template</label>
                                                    <input type="text" :name="'actions[' + index + '][to]'"
                                                        x-model="action.to" placeholder="Recipient Email"
                                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-xs sm:leading-5">
                                                </div>
                                                <div x-show="action.type === 'webhook'" class="col-span-4">
                                                    <label
                                                        class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1">URL</label>
                                                    <input type="text" :name="'actions[' + index + '][url]'"
                                                        x-model="action.url" placeholder="https://..."
                                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-xs sm:leading-5">
                                                </div>
                                                <div x-show="action.type === 'notification'" class="col-span-4">
                                                    <label
                                                        class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1">Message</label>
                                                    <input type="text" :name="'actions[' + index + '][message]'"
                                                        x-model="action.message" placeholder="Notification text"
                                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-xs sm:leading-5">
                                                </div>
                                                <div x-show="action.type === 'status_update'"
                                                    class="col-span-4 box-border">
                                                    <div class="grid grid-cols-2 gap-4">
                                                        <div>
                                                            <label
                                                                class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1">Field</label>
                                                            <input type="text" :name="'actions[' + index + '][field]'"
                                                                x-model="action.field" placeholder="status"
                                                                class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-xs sm:leading-5">
                                                        </div>
                                                        <div>
                                                            <label
                                                                class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1">Value</label>
                                                            <input type="text" :name="'actions[' + index + '][value]'"
                                                                x-model="action.value" placeholder="hot"
                                                                class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-xs sm:leading-5">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Step Settings Row --}}
                                            <div class="grid grid-cols-3 gap-4 mt-3 pt-3 border-t border-slate-100">
                                                <div>
                                                    <label
                                                        class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1">Delay
                                                        (s)</label>
                                                    <input type="number" :name="'actions[' + index + '][delay]'"
                                                        x-model="action.delay" min="0" placeholder="0"
                                                        class="block w-full rounded-md border-0 py-1 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-xs sm:leading-5">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1">Retries</label>
                                                    <input type="number" :name="'actions[' + index + '][retry_limit]'"
                                                        x-model="action.retry_limit" min="0" placeholder="0"
                                                        class="block w-full rounded-md border-0 py-1 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-xs sm:leading-5">
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1">Policy</label>
                                                    <select :name="'actions[' + index + '][on_failure]'"
                                                        x-model="action.on_failure"
                                                        class="block w-full rounded-md border-0 py-1 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-xs sm:leading-5">
                                                        <option value="continue">Continue</option>
                                                        <option value="halt">Halt</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Step Conditions Builder -->
                                            <div class="mt-3 pt-3 border-t border-slate-100">
                                                <div class="flex items-center justify-between mb-2">
                                                    <div class="flex items-center space-x-2">
                                                        <span
                                                            class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Execution
                                                            Conditions</span>
                                                        <select :name="'actions[' + index + '][condition_logic]'"
                                                            x-model="action.condition_logic"
                                                            class="text-[9px] font-bold border-slate-200 rounded py-0 bg-white focus:ring-indigo-600">
                                                            <option value="AND">AND</option>
                                                            <option value="OR">OR</option>
                                                        </select>
                                                    </div>
                                                    <button type="button" @click="addCondition(action)"
                                                        class="text-[10px] font-semibold text-indigo-600 hover:text-indigo-500 uppercase tracking-wide hover:underline">+
                                                        Condition</button>
                                                </div>
                                                <div class="space-y-2">
                                                    <template x-for="(condition, cIndex) in action.conditions"
                                                        :key="cIndex">
                                                        <div
                                                            class="grid grid-cols-12 gap-2 p-1.5 bg-slate-50 rounded border border-slate-100 relative items-end">
                                                            <div class="col-span-3">
                                                                <label
                                                                    class="block text-[9px] font-bold text-slate-400 uppercase mb-0.5">Type</label>
                                                                <select
                                                                    :name="'actions[' + index + '][conditions][' + cIndex + '][type]'"
                                                                    x-model="condition.type"
                                                                    class="block w-full rounded border-slate-200 py-0.5 text-[10px] focus:ring-indigo-600">
                                                                    <option value="payload">Payload</option>
                                                                    <option value="entity">Entity</option>
                                                                    <option value="counts">Counts</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-span-3">
                                                                <label
                                                                    class="block text-[9px] font-bold text-slate-400 uppercase mb-0.5"
                                                                    x-text="condition.type === 'counts' ? 'Event' : 'Field'"></label>
                                                                <input type="text"
                                                                    :name="'actions[' + index + '][conditions][' + cIndex + '][field]'"
                                                                    x-model="condition.field" placeholder="field"
                                                                    class="block w-full rounded border-slate-200 py-0.5 text-[10px] focus:ring-indigo-600">
                                                            </div>
                                                            <div class="col-span-2">
                                                                <label
                                                                    class="block text-[9px] font-bold text-slate-400 uppercase mb-0.5">Op</label>
                                                                <select
                                                                    :name="'actions[' + index + '][conditions][' + cIndex + '][operator]'"
                                                                    x-model="condition.operator"
                                                                    class="block w-full rounded border-slate-200 py-0.5 text-[10px] focus:ring-indigo-600">
                                                                    <option value="=">=</option>
                                                                    <option value=">">&gt;</option>
                                                                    <option value="<">&lt;</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-span-3">
                                                                <label
                                                                    class="block text-[9px] font-bold text-slate-400 uppercase mb-0.5">Value</label>
                                                                <input type="text"
                                                                    :name="'actions[' + index + '][conditions][' + cIndex + '][value]'"
                                                                    x-model="condition.value" placeholder="value"
                                                                    class="block w-full rounded border-slate-200 py-0.5 text-[10px] focus:ring-indigo-600">
                                                            </div>
                                                            <div
                                                                class="col-span-1 border-l border-slate-200 pl-1 flex justify-center">
                                                                <button type="button"
                                                                    @click="removeCondition(action, cIndex)"
                                                                    class="text-slate-400 hover:text-red-500">
                                                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24"
                                                                        stroke-width="2" stroke="currentColor">
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round"
                                                                            d="M6 18L18 6M6 6l12 12" />
                                                                    </svg>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                                <div x-show="!action.conditions || action.conditions.length === 0"
                                                    class="mt-1 text-[9px] text-slate-400 italic text-center">No
                                                    step conditions (step always executes).</div>
                                            </div>
                                        </div>
                                    </template>
                                    <div x-show="!editingRule.actions || editingRule.actions.length === 0"
                                        class="text-center py-8 text-xs text-slate-400 italic bg-white rounded-lg border border-slate-100 border-dashed">
                                        No steps defined. Click "Add Step" to define actions.
                                    </div>
                                </div>
                            </div>

                            <!-- Safety Settings -->
                            <div class="p-4 bg-slate-50 rounded-lg ring-1 ring-slate-900/5"
                                x-data="{ safetyExpanded: true }">
                                <button type="button" @click="safetyExpanded = !safetyExpanded"
                                    class="flex items-center gap-2 text-xs font-bold text-slate-500 uppercase tracking-wider hover:text-slate-700 transition-colors mb-3">
                                    <svg class="h-4 w-4 transition-transform duration-200"
                                        :class="safetyExpanded ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24"
                                        stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                    </svg>
                                    Safety & Limits
                                </button>
                                <div x-show="safetyExpanded" x-collapse
                                    class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                                    <div class="col-span-2 sm:col-span-1">
                                        <label class="block text-xs font-medium text-slate-700 mb-1">Rate Limit
                                            (Runs)</label>
                                        <input type="number" name="settings[rate_limit_count]" placeholder="Unlimited"
                                            class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                    </div>
                                    <div class="col-span-2 sm:col-span-1">
                                        <label class="block text-xs font-medium text-slate-700 mb-1">Period
                                            (Minutes)</label>
                                        <input type="number" name="settings[rate_limit_period]" value="1440"
                                            class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                    </div>
                                    <div class="col-span-2">
                                        <label class="block text-xs font-medium text-slate-700 mb-1">Max Executions per
                                            Entity</label>
                                        <input type="number" name="settings[max_executions_per_entity]"
                                            placeholder="Unlimited"
                                            class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                        <p class="mt-1 text-[10px] text-slate-500">Limits how many times this
                                            rule can run for a specific entity ID (e.g. per Order ID).</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button type="submit"
                            class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:ml-3 sm:w-auto">
                            Create Rule
                        </button>
                        <button type="button" @click="showCreateModal = false"
                            class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
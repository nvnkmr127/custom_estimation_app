<div x-show="showEditModal" class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true"
    style="display: none;">
    <div class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity"></div>
    <div class="fixed inset-0 z-50 w-screen overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div
                class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-3xl">
                <form :action="'/automation/' + editingRule.id" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <h3 class="text-base font-semibold leading-6 text-slate-900 mb-5">Edit Automation Rule
                        </h3>
                        <div class="space-y-6">
                            <div class="grid grid-cols-2 gap-5">
                                <div class="col-span-2 sm:col-span-1">
                                    <label
                                        class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Rule
                                        Name</label>
                                    <input type="text" name="name" x-model="editingRule.name" required
                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                </div>
                                <div class="col-span-2 sm:col-span-1">
                                    <label
                                        class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Trigger
                                        Event</label>
                                    <select name="trigger_event" x-model="editingRule.trigger_event"
                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                        @foreach ($events as $event)
                                            <option value="{{ $event }}">{{ $event }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Global Conditions -->
                            <div class="p-4 bg-slate-50 rounded-lg ring-1 ring-slate-900/5">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Global
                                        Entry Conditions</h4>
                                    <button type="button" @click="addCondition(editingRule)"
                                        class="text-xs font-semibold text-indigo-600 hover:text-indigo-500 hover:underline">+
                                        Add Condition</button>
                                </div>
                                <div class="space-y-2">
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
                                                    <option value="time_of_day">Time of Day</option>
                                                    <option value="day_of_week">Day of Week</option>
                                                </select>
                                            </div>
                                            <div class="col-span-3">
                                                <label
                                                    class="text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1 block"
                                                    x-text="condition.type === 'counts' ? 'Event' : (['time_of_day', 'day_of_week'].includes(condition.type) ? 'Ignored' : 'Field')"></label>
                                                <input type="text" :name="'conditions[' + cIndex + '][field]'"
                                                    x-model="condition.field"
                                                    :placeholder="['time_of_day', 'day_of_week'].includes(condition.type) ? 'N/A' : 'e.g. status'"
                                                    :disabled="['time_of_day', 'day_of_week'].includes(condition.type)"
                                                    class="block w-full rounded-md border-0 py-1 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-xs sm:leading-5 disabled:bg-slate-50 disabled:text-slate-500">
                                            </div>
                                            <div class="col-span-2">
                                                <label
                                                    class="text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1 block">Op</label>
                                                <select :name="'conditions[' + cIndex + '][operator]'"
                                                    x-model="condition.operator"
                                                    :disabled="['time_of_day', 'day_of_week'].includes(condition.type)"
                                                    class="block w-full rounded-md border-0 py-1 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-xs sm:leading-5 disabled:bg-slate-50 disabled:text-slate-500">
                                                    <template x-if="condition.type === 'time_of_day'">
                                                        <option value="between" selected>Between</option>
                                                    </template>
                                                    <template x-if="condition.type === 'day_of_week'">
                                                        <option value="in" selected>In</option>
                                                    </template>
                                                    <template
                                                        x-if="!['time_of_day', 'day_of_week'].includes(condition.type)">
                                                        <optgroup label="Standard">
                                                            <option value="=">=</option>
                                                            <option value=">">&gt;</option>
                                                            <option value="<">&lt;</option>
                                                            <option value=">=">&gt;=</option>
                                                            <option value="<=">&lt;=</option>
                                                            <option value="!=">!=</option>
                                                        </optgroup>
                                                    </template>
                                                </select>
                                            </div>
                                            <div class="col-span-3">
                                                <label
                                                    class="text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1 block">Value</label>
                                                <input type="text" :name="'conditions[' + cIndex + '][value]'"
                                                    x-model="condition.value"
                                                    :placeholder="condition.type === 'time_of_day' ? '09:00-17:00' : (condition.type === 'day_of_week' ? '1,2,3,4,5' : 'value')"
                                                    class="block w-full rounded-md border-0 py-1 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-xs sm:leading-5">
                                                <p x-show="['time_of_day', 'day_of_week'].includes(condition.type)"
                                                    class="text-[9px] text-slate-500 mt-0.5"
                                                    x-text="condition.type === 'time_of_day' ? 'Format: HH:MM-HH:MM' : '0=Sun, 1=Mon, ..., 6=Sat'">
                                                </p>
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
                                        class="text-center py-2 text-xs text-slate-400 italic">No entry
                                        conditions (runs for all triggers).</div>
                                </div>
                            </div>

                            <!-- Workflow Steps -->
                            <div class="p-4 bg-slate-50 rounded-lg ring-1 ring-slate-900/5">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                                        Workflow
                                        Steps</h4>
                                    <button type="button" @click="addAction(editingRule)"
                                        class="text-xs font-semibold text-indigo-600 hover:text-indigo-500 hover:underline">+
                                        Add Step</button>
                                </div>
                                <div class="mt-2 space-y-3">
                                    <template x-for="(action, index) in editingRule.actions" :key="index">
                                        <div class="p-4 bg-white rounded-lg shadow-sm ring-1 ring-slate-900/5 relative">
                                            <div class="grid grid-cols-6 gap-4">
                                                <div class="col-span-2">
                                                    <label
                                                        class="text-xs font-semibold text-slate-500 uppercase">Type</label>
                                                    <select :name="'actions[' + index + '][type]'" x-model="action.type"
                                                        class="block w-full mt-1 rounded-md border-0 py-1 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 sm:text-xs">
                                                        <option value="email">Send Email</option>
                                                        <option value="webhook">POST Webhook</option>
                                                        <option value="notification">Internal Notification
                                                        </option>
                                                        <option value="status_update">Update Status/Field
                                                        </option>
                                                    </select>
                                                </div>
                                                <div x-show="action.type === 'email'">
                                                    <label class="text-xs font-semibold text-slate-500 uppercase">To
                                                        / Template</label>
                                                    <input type="text" :name="'actions[' + index + '][to]'"
                                                        x-model="action.to" placeholder="Recipient Email"
                                                        class="block w-full mt-1 rounded-md border-0 py-1 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 sm:text-xs">
                                                </div>
                                                <div x-show="action.type === 'webhook'">
                                                    <label
                                                        class="text-xs font-semibold text-slate-500 uppercase">URL</label>
                                                    <input type="text" :name="'actions[' + index + '][url]'"
                                                        x-model="action.url" placeholder="https://..."
                                                        class="block w-full mt-1 rounded-md border-0 py-1 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 sm:text-xs">
                                                </div>
                                                <div x-show="action.type === 'notification'">
                                                    <label
                                                        class="text-xs font-semibold text-slate-500 uppercase">Message</label>
                                                    <input type="text" :name="'actions[' + index + '][message]'"
                                                        x-model="action.message" placeholder="Notification text"
                                                        class="block w-full mt-1 rounded-md border-0 py-1 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 sm:text-xs">
                                                </div>
                                                <div x-show="action.type === 'status_update'">
                                                    <div class="grid grid-cols-2 gap-2">
                                                        <div>
                                                            <label
                                                                class="text-xs font-semibold text-slate-500 uppercase">Field</label>
                                                            <input type="text" :name="'actions[' + index + '][field]'"
                                                                x-model="action.field" placeholder="status"
                                                                class="block w-full mt-1 rounded-md border-0 py-1 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 sm:text-xs">
                                                        </div>
                                                        <div>
                                                            <label
                                                                class="text-xs font-semibold text-slate-500 uppercase">Value</label>
                                                            <input type="text" :name="'actions[' + index + '][value]'"
                                                                x-model="action.value" placeholder="hot"
                                                                class="block w-full mt-1 rounded-md border-0 py-1 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 sm:text-xs">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-span-1 grid grid-cols-3 gap-2">
                                                    <div>
                                                        <label
                                                            class="text-[10px] font-bold text-slate-500 uppercase">Delay
                                                            (s)</label>
                                                        <input type="number" :name="'actions[' + index + '][delay]'"
                                                            x-model="action.delay" min="0"
                                                            class="block w-full mt-1 rounded-md border-0 py-1 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 sm:text-xs">
                                                    </div>
                                                    <div>
                                                        <label
                                                            class="text-[10px] font-bold text-slate-500 uppercase">Retries</label>
                                                        <input type="number"
                                                            :name="'actions[' + index + '][retry_limit]'"
                                                            x-model="action.retry_limit" min="0" placeholder="0"
                                                            class="block w-full mt-1 rounded-md border-0 py-1 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 sm:text-xs">
                                                    </div>
                                                    <div>
                                                        <label
                                                            class="text-[10px] font-bold text-slate-500 uppercase">Policy</label>
                                                        <select :name="'actions[' + index + '][on_failure]'"
                                                            x-model="action.on_failure"
                                                            class="block w-full mt-1 rounded-md border-0 py-1 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 sm:text-xs">
                                                            <option value="continue">Continue</option>
                                                            <option value="halt">Halt</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Step Conditions Builder -->
                                            <div class="mt-3 pt-3 border-t border-slate-200">
                                                <div class="flex items-center justify-between mb-2">
                                                    <div class="flex items-center space-x-2">
                                                        <span
                                                            class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Step
                                                            Execution Conditions</span>
                                                        <select :name="'actions[' + index + '][condition_logic]'"
                                                            x-model="action.condition_logic"
                                                            class="text-[9px] font-bold border-slate-200 rounded py-0 bg-white">
                                                            <option value="AND">AND</option>
                                                            <option value="OR">OR</option>
                                                        </select>
                                                    </div>
                                                    <button type="button" @click="addCondition(action)"
                                                        class="text-[10px] font-semibold text-indigo-600 hover:text-indigo-500 uppercase">+
                                                        Add Condition</button>
                                                </div>
                                                <div class="space-y-2">
                                                    <template x-for="(condition, cIndex) in action.conditions"
                                                        :key="cIndex">
                                                        <div
                                                            class="grid grid-cols-12 gap-2 p-1.5 bg-white rounded border border-slate-100 relative items-end">
                                                            <div class="col-span-3">
                                                                <label
                                                                    class="text-[9px] font-bold text-slate-400 uppercase">Type</label>
                                                                <select
                                                                    :name="'actions[' + index + '][conditions][' + cIndex + '][type]'"
                                                                    x-model="condition.type"
                                                                    class="block w-full rounded border-slate-200 py-0.5 text-[10px]">
                                                                    <option value="payload">Payload</option>
                                                                    <option value="entity">Entity</option>
                                                                    <option value="counts">Counts</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-span-3">
                                                                <label
                                                                    class="text-[9px] font-bold text-slate-400 uppercase"
                                                                    x-text="condition.type === 'counts' ? 'Event' : 'Field'"></label>
                                                                <input type="text"
                                                                    :name="'actions[' + index + '][conditions][' + cIndex + '][field]'"
                                                                    x-model="condition.field" placeholder="field"
                                                                    class="block w-full rounded border-slate-200 py-0.5 text-[10px]">
                                                            </div>
                                                            <div class="col-span-2">
                                                                <label
                                                                    class="text-[9px] font-bold text-slate-400 uppercase">Op</label>
                                                                <select
                                                                    :name="'actions[' + index + '][conditions][' + cIndex + '][operator]'"
                                                                    x-model="condition.operator"
                                                                    class="block w-full rounded border-slate-200 py-0.5 text-[10px]">
                                                                    <option value="=">=</option>
                                                                    <option value=">">&gt;</option>
                                                                    <option value="<">&lt;</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-span-3">
                                                                <label
                                                                    class="text-[9px] font-bold text-slate-400 uppercase">Value</label>
                                                                <input type="text"
                                                                    :name="'actions[' + index + '][conditions][' + cIndex + '][value]'"
                                                                    x-model="condition.value" placeholder="value"
                                                                    class="block w-full rounded border-slate-200 py-0.5 text-[10px]">
                                                            </div>
                                                            <div class="col-span-1 border-l border-slate-50 pl-1">
                                                                <button type="button"
                                                                    @click="removeCondition(action, cIndex)"
                                                                    class="text-slate-300 hover:text-red-500">
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
                                                    step conditions (always executes).</div>
                                            </div>

                                            <button type="button" @click="removeAction(editingRule, index)"
                                                class="absolute top-2 right-2 text-slate-400 hover:text-red-500">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- Safety Settings -->
                            <div class="p-4 bg-slate-50 rounded-lg ring-1 ring-slate-900/5">
                                <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4">
                                    Safety Controls</h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label
                                            class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1">Rate
                                            Limit (Events)</label>
                                        <input type="number" name="settings[rate_limit_count]"
                                            x-model="editingRule.settings.rate_limit_count" placeholder="e.g. 5"
                                            class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-xs sm:leading-5">
                                    </div>
                                    <div>
                                        <label
                                            class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1">Period
                                            (Minutes)</label>
                                        <input type="number" name="settings[rate_limit_period]"
                                            x-model="editingRule.settings.rate_limit_period" placeholder="1440"
                                            class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-xs sm:leading-5">
                                    </div>
                                    <div>
                                        <label
                                            class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1">Max
                                            per Entity</label>
                                        <input type="number" name="settings[max_executions_per_entity]"
                                            x-model="editingRule.settings.max_executions_per_entity"
                                            placeholder="e.g. 1"
                                            class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-xs sm:leading-5">
                                    </div>
                                    <div class="flex items-center gap-2 mt-5">
                                        <input type="checkbox" name="settings[is_enabled]"
                                            x-model="editingRule.settings.is_enabled"
                                            class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-600">
                                        <label class="text-xs font-medium text-slate-900 uppercase">Internal
                                            Enabled</label>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <input type="checkbox" name="is_active" x-model="editingRule.is_active" value="1"
                                    class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-600">
                                <label class="text-sm font-medium text-slate-900">Active</label>
                            </div>

                            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                                <button type="button" @click="saveAsTemplate()"
                                    class="mr-auto inline-flex justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
                                    Save as Template
                                </button>
                                <button @click="showEditModal = false" type="button"
                                    class="inline-flex justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Cancel</button>
                                <button type="submit"
                                    class="inline-flex justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Update
                                    Rule</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
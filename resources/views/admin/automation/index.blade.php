<x-app-layout>
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Marketing Automation Layer</h1>
            <p class="mt-2 text-sm text-slate-500">Configure event-driven workflows to trigger Emails, Webhooks, and
                Notifications.</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            <a href="{{ route('settings.edit') }}" class="font-semibold text-indigo-600 hover:text-indigo-500">
                &larr; Back to Global Settings
            </a>
        </div>
    </div>

    <!-- Rules List -->
    <div class="mt-8" x-data="automationPageData()">
        <div class="sm:flex sm:items-center sm:justify-between">
            <h2 class="text-lg font-medium leading-6 text-slate-900">Automation Rules</h2>
            <button
                @click="showCreateModal = true; editingRule = { id: null, name: '', trigger_event: '', condition_logic: 'AND', conditions: [], actions: [{type: 'email', to: '', field: '', value: '', delay: 0, conditions: []}], settings: { is_enabled: true, rate_limit_count: null, rate_limit_period: 1440, max_executions_per_entity: null }, is_active: true }"
                type="button"
                class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                Create New Rule
            </button>
        </div>

        <div class="mt-8 flow-root">
            <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-900/5 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col"
                                    class="py-3.5 pl-6 pr-3 text-left text-xs font-semibold text-slate-900 sm:pl-6">
                                    Workflow / Version
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-slate-900">
                                    Trigger Event</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-slate-900">
                                    Channels / Steps</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold text-slate-900">
                                    Status</th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-6 sm:pr-6">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            @forelse($rules as $rule)
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="whitespace-nowrap py-4 pl-6 pr-3 text-sm font-medium text-slate-900 sm:pl-6">
                                        <div class="flex flex-col">
                                            <span>{{ $rule->name }}</span>
                                            <span
                                                class="text-[10px] text-slate-400 font-normal italic">v{{ $rule->version }}</span>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                        <span
                                            class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">{{ $rule->triggers->first()->event_name ?? 'N/A' }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                        <div class="flex gap-1 flex-wrap max-w-xs">
                                            @foreach($rule->steps as $step)
                                                @php
                                                    $channelColors = [
                                                        'email' => 'bg-indigo-50 text-indigo-700 ring-indigo-700/10',
                                                        'webhook' => 'bg-emerald-50 text-emerald-700 ring-emerald-700/10',
                                                        'notification' => 'bg-amber-50 text-amber-700 ring-amber-700/10',
                                                        'status_update' => 'bg-slate-50 text-slate-700 ring-slate-700/10',
                                                    ];
                                                    $colorClass = $channelColors[$step->action->type ?? ''] ?? 'bg-purple-50 text-purple-700 ring-purple-700/10';
                                                @endphp
                                                <span
                                                    class="inline-flex items-center rounded-md px-2 py-1 text-[10px] font-medium ring-1 ring-inset {{ $colorClass }}">
                                                    {{ strtoupper($step->action->type ?? 'Step') }}
                                                    @if($step->delay > 0)
                                                        <span class="ml-1 opacity-60">+{{ $step->delay }}s</span>
                                                    @endif
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                        @if($rule->is_active)
                                            <span
                                                class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Active</span>
                                        @else
                                            <span
                                                class="inline-flex items-center rounded-full bg-slate-50 px-2.5 py-0.5 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-500/10">Inactive</span>
                                        @endif
                                    </td>
                                    <td
                                        class="relative whitespace-nowrap py-4 pl-3 pr-6 text-right text-sm font-medium sm:pr-6">
                                        <div class="flex justify-end gap-3 items-center opacity-80 hover:opacity-100 transition-opacity">
                                            <button @click="viewMetrics({{ $rule->id }})"
                                                class="text-emerald-600 hover:text-emerald-900 transition-colors" title="Performance Metrics">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                                </svg>
                                            </button>
                                            <button @click="viewLogs({{ $rule->id }})"
                                                class="text-slate-600 hover:text-slate-900 transition-colors" title="Execution Logs">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            </button>
                                            <form action="{{ route('automation.version', $rule) }}" method="POST"
                                                class="inline">
                                                @csrf
                                                <button type="submit" class="text-blue-600 hover:text-blue-900 transition-colors"
                                                    title="Clone as New Version">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V7a2 2 0 01-2 2h-2M3 18v-6a2 2 0 012-2h11" />
                                                    </svg>
                                                </button>
                                            </form>
                                            <button @click='editRule(@json($rule))' class="text-indigo-600 hover:text-indigo-900 transition-colors"
                                                title="Edit">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <form action="{{ route('automation.destroy', $rule) }}" method="POST"
                                                class="inline"
                                                onsubmit="return confirm('Are you sure? This will delete this version permanently.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-600 hover:text-rose-900 transition-colors"
                                                    title="Delete">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-12 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="h-10 w-10 rounded-full bg-slate-50 flex items-center justify-center mb-3">
                                                 <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                                  </svg>
                                            </div>
                                            <h3 class="mt-2 text-sm font-semibold text-slate-900">No automation rules</h3>
                                            <p class="mt-1 text-sm text-slate-500">Get started by creating a new workflow.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <div x-show="showCreateModal" class="relative z-50" aria-labelledby="modal-title" role="dialog"
            aria-modal="true" style="display: none;">
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
                                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Rule
                                                Name</label>
                                            <input type="text" name="name" required
                                                placeholder="e.g. Welcome Email for New Users"
                                                class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                        </div>
                                        <div class="col-span-2 sm:col-span-1">
                                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Trigger
                                                Event</label>
                                            <select name="trigger_event"
                                                class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                                @foreach($events as $event)
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
                                            <template x-for="(condition, cIndex) in editingRule.conditions"
                                                :key="cIndex">
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
                                                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1 block"
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
                                                        <button type="button"
                                                            @click="removeCondition(editingRule, cIndex)"
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
                                    <div class="p-4 bg-slate-50 rounded-lg ring-1 ring-slate-900/5">
                                        <div class="flex items-center justify-between mb-3">
                                            <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Workflow
                                                Steps</h4>
                                            <button type="button" @click="addAction(editingRule)"
                                                class="text-xs font-semibold text-indigo-600 hover:text-indigo-500 hover:underline">+
                                                Add Step</button>
                                        </div>
                                        <div class="space-y-3">
                                            <template x-for="(action, index) in editingRule.actions" :key="index">
                                                <div
                                                    class="p-4 bg-white rounded-lg shadow-sm ring-1 ring-slate-900/5 relative">
                                                    
                                                    <div class="grid grid-cols-6 gap-4">
                                                        <div class="col-span-2">
                                                            <label
                                                                class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1">Type</label>
                                                            <select :name="'actions[' + index + '][type]'"
                                                                x-model="action.type"
                                                                class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-xs sm:leading-5">
                                                                <option value="email">Send Email</option>
                                                                <option value="webhook">POST Webhook</option>
                                                                <option value="notification">Internal Notification
                                                                </option>
                                                                <option value="status_update">Update Status/Field
                                                                </option>
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
                                                        <div x-show="action.type === 'status_update'" class="col-span-4 box-border">
                                                            <div class="grid grid-cols-2 gap-4">
                                                                <div>
                                                                    <label
                                                                        class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1">Field</label>
                                                                    <input type="text"
                                                                        :name="'actions[' + index + '][field]'"
                                                                        x-model="action.field" placeholder="status"
                                                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-xs sm:leading-5">
                                                                </div>
                                                                <div>
                                                                    <label
                                                                        class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1">Value</label>
                                                                    <input type="text"
                                                                        :name="'actions[' + index + '][value]'"
                                                                        x-model="action.value" placeholder="hot"
                                                                        class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-xs sm:leading-5">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- Step Settings Row --}}
                                                    <div class="grid grid-cols-3 gap-4 mt-3 pt-3 border-t border-slate-100">
                                                        <div>
                                                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1">Delay
                                                                (s)</label>
                                                            <input type="number" :name="'actions[' + index + '][delay]'"
                                                                x-model="action.delay" min="0" placeholder="0"
                                                                class="block w-full rounded-md border-0 py-1 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-xs sm:leading-5">
                                                        </div>
                                                        <div>
                                                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1">Retries</label>
                                                            <input type="number"
                                                                :name="'actions[' + index + '][retry_limit]'"
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
                                                                    class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Execution Conditions</span>
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
                                                                            x-model="condition.field"
                                                                            placeholder="field"
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
                                                                            x-model="condition.value"
                                                                            placeholder="value"
                                                                            class="block w-full rounded border-slate-200 py-0.5 text-[10px] focus:ring-indigo-600">
                                                                    </div>
                                                                    <div
                                                                        class="col-span-1 border-l border-slate-200 pl-1 flex justify-center">
                                                                        <button type="button"
                                                                            @click="removeCondition(action, cIndex)"
                                                                            class="text-slate-400 hover:text-red-500">
                                                                            <svg class="h-3 w-3" fill="none"
                                                                                viewBox="0 0 24 24" stroke-width="2"
                                                                                stroke="currentColor">
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

                                                    <button type="button" @click="removeAction(editingRule, index)"
                                                        class="absolute top-3 right-3 text-slate-400 hover:text-red-500 transition-colors">
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                            stroke-width="2" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </template>
                                            <div x-show="!editingRule.actions || editingRule.actions.length === 0"
                                                class="text-center py-8 text-xs text-slate-400 italic bg-white rounded-lg border border-slate-100 border-dashed">
                                                No steps defined. Click "Add Step" to define actions.
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Safety Settings -->
                                    <div class="p-4 bg-slate-50 rounded-lg ring-1 ring-slate-900/5">
                                        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4">
                                            Safety Controls</h4>
                                        <div class="grid grid-cols-2 gap-5">
                                            <div>
                                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1">Rate
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
                                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1">Max
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
                                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Internal
                                                    Enabled</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                                         <button @click="showCreateModal = false" type="button"
                                            class="inline-flex justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">Cancel</button>
                                        <button type="submit"
                                            class="inline-flex justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Create
                                            Rule</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Modal (Simplified for this example, usually a separate view or more complex Alpine) -->
        <div x-show="showEditModal" class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true"
            style="display: none;">
            <div class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity"></div>
            <div class="fixed inset-0 z-50 w-screen overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div
                        class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-3xl">
                        <form :action="'/admin/automation/' + editingRule.id" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                                <h3 class="text-base font-semibold leading-6 text-slate-900 mb-5">Edit Automation Rule
                                </h3>
                                <div class="space-y-6">
                                    <div class="grid grid-cols-2 gap-5">
                                        <div class="col-span-2 sm:col-span-1">
                                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Rule
                                                Name</label>
                                            <input type="text" name="name" x-model="editingRule.name" required
                                                class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                        </div>
                                        <div class="col-span-2 sm:col-span-1">
                                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Trigger
                                                Event</label>
                                            <select name="trigger_event" x-model="editingRule.trigger_event"
                                                class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                                @foreach($events as $event)
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
                                            <template x-for="(condition, cIndex) in editingRule.conditions"
                                                :key="cIndex">
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
                                                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1 block"
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
                                                        <button type="button"
                                                            @click="removeCondition(editingRule, cIndex)"
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
                                            <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Workflow
                                                Steps</h4>
                                            <button type="button" @click="addAction(editingRule)"
                                                class="text-xs font-semibold text-indigo-600 hover:text-indigo-500 hover:underline">+
                                                Add Step</button>
                                        </div>
                                        <div class="mt-2 space-y-3">
                                            <template x-for="(action, index) in editingRule.actions" :key="index">
                                                <div
                                                    class="p-4 bg-white rounded-lg shadow-sm ring-1 ring-slate-900/5 relative">
                                                    <div class="grid grid-cols-6 gap-4">
                                                        <div class="col-span-2">
                                                            <label
                                                                class="text-xs font-semibold text-slate-500 uppercase">Type</label>
                                                            <select :name="'actions[' + index + '][type]'"
                                                                x-model="action.type"
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
                                                            <label
                                                                class="text-xs font-semibold text-slate-500 uppercase">To
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
                                                                    <input type="text"
                                                                        :name="'actions[' + index + '][field]'"
                                                                        x-model="action.field" placeholder="status"
                                                                        class="block w-full mt-1 rounded-md border-0 py-1 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 sm:text-xs">
                                                                </div>
                                                                <div>
                                                                    <label
                                                                        class="text-xs font-semibold text-slate-500 uppercase">Value</label>
                                                                    <input type="text"
                                                                        :name="'actions[' + index + '][value]'"
                                                                        x-model="action.value" placeholder="hot"
                                                                        class="block w-full mt-1 rounded-md border-0 py-1 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 sm:text-xs">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-span-1 grid grid-cols-3 gap-2">
                                                            <div>
                                                                <label class="text-[10px] font-bold text-slate-500 uppercase">Delay (s)</label>
                                                                <input type="number" :name="'actions[' + index + '][delay]'"
                                                                    x-model="action.delay" min="0"
                                                                    class="block w-full mt-1 rounded-md border-0 py-1 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 sm:text-xs">
                                                            </div>
                                                            <div>
                                                                <label class="text-[10px] font-bold text-slate-500 uppercase">Retries</label>
                                                                <input type="number" :name="'actions[' + index + '][retry_limit]'"
                                                                    x-model="action.retry_limit" min="0" placeholder="0"
                                                                    class="block w-full mt-1 rounded-md border-0 py-1 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 sm:text-xs">
                                                            </div>
                                                            <div>
                                                                <label class="text-[10px] font-bold text-slate-500 uppercase">Policy</label>
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
                                                                <select
                                                                    :name="'actions[' + index + '][condition_logic]'"
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
                                                                            x-model="condition.field"
                                                                            placeholder="field"
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
                                                                            x-model="condition.value"
                                                                            placeholder="value"
                                                                            class="block w-full rounded border-slate-200 py-0.5 text-[10px]">
                                                                    </div>
                                                                    <div
                                                                        class="col-span-1 border-l border-slate-50 pl-1">
                                                                        <button type="button"
                                                                            @click="removeCondition(action, cIndex)"
                                                                            class="text-slate-300 hover:text-red-500">
                                                                            <svg class="h-3 w-3" fill="none"
                                                                                viewBox="0 0 24 24" stroke-width="2"
                                                                                stroke="currentColor">
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
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                            stroke-width="1.5" stroke="currentColor">
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
                                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1">Rate
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
                                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1">Max
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
                                        <input type="checkbox" name="is_active" x-model="editingRule.is_active"
                                            value="1"
                                            class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-600">
                                        <label class="text-sm font-medium text-slate-900">Active</label>
                                    </div>

                                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
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

        <!-- Metrics Modal -->
        <div x-show="showMetricsModal" class="relative z-10" aria-labelledby="modal-title" role="dialog"
            aria-modal="true" style="display: none;">
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
                                        <div class="mt-1 text-2xl font-bold text-blue-900"
                                            x-text="metrics.trigger_count"></div>
                                    </div>
                                    <div class="p-4 bg-green-50 rounded-lg border border-green-100">
                                        <div class="text-[10px] font-bold text-green-400 uppercase">Completion Rate
                                        </div>
                                        <div class="mt-1 text-2xl font-bold text-green-900"
                                            x-text="metrics.completion_rate + '%'"></div>
                                    </div>
                                    <div class="p-4 bg-red-50 rounded-lg border border-red-100">
                                        <div class="text-[10px] font-bold text-red-400 uppercase">Failure Rate</div>
                                        <div class="mt-1 text-2xl font-bold text-red-900"
                                            x-text="metrics.failure_rate + '%'"></div>
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

        <!-- Logs Modal (Grouped Trace View) -->
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

                            <div class="max-h-[65vh] overflow-y-auto pr-2 custom-scrollbar">
                                <div x-show="loadingLogs" class="py-10 text-center italic text-slate-500">Loading
                                    traces...</div>

                                <div x-show="!loadingLogs" class="space-y-4">
                                    <template x-for="(trace, eventId) in logs" :key="eventId">
                                        <div class="border border-slate-200 rounded-lg overflow-hidden">
                                            <div
                                                class="bg-slate-50 px-3 py-2 flex items-center justify-between border-b border-slate-200">
                                                <div class="flex items-center gap-3">
                                                    <span class="text-[10px] font-mono text-slate-400"
                                                        x-text="'#' + eventId.substring(0,8)"></span>
                                                    <span class="text-xs font-semibold text-slate-700"
                                                        x-text="new Date(trace[0].created_at).toLocaleString()"></span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span
                                                        :class="trace.every(l => l.status === 'success') ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'"
                                                        class="text-[9px] font-bold px-1.5 py-0.5 rounded uppercase"
                                                        x-text="trace.every(l => l.status === 'success') ? 'Completed' : 'Partial/Failed'"></span>
                                                </div>
                                            </div>
                                            <div class="divide-y divide-slate-100 bg-white">
                                                <template x-for="log in trace" :key="log.id">
                                                    <div
                                                        class="px-4 py-2 flex items-center justify-between hover:bg-slate-50 transition-colors">
                                                        <div class="flex gap-4 items-center">
                                                            <div class="w-1.5 h-1.5 rounded-full" :class="{
                                                                'bg-green-500': log.status === 'success',
                                                                'bg-red-500': log.status === 'failed',
                                                                'bg-blue-500': log.status === 'pending'
                                                            }"></div>
                                                            <div class="flex flex-col">
                                                                <span class="text-xs font-medium text-slate-900"
                                                                    x-text="log.action_type.toUpperCase()"></span>
                                                                <span class="text-[10px] text-slate-500"
                                                                    x-text="log.step?.order !== undefined ? 'Step ' + (parseInt(log.step.order) + 1) : 'Global'"></span>
                                                            </div>
                                                        </div>
                                                        <div class="flex items-center gap-4">
                                                            <span x-show="log.error"
                                                                class="text-[10px] text-red-600 bg-red-50 px-2 py-1 rounded"
                                                                x-text="log.error"></span>
                                                            <span class="text-[10px] text-slate-400"
                                                                x-text="new Date(log.created_at).toLocaleTimeString()"></span>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="Object.keys(logs).length === 0">
                                        <div class="py-12 text-center text-slate-400 italic">No execution traces found.
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <button @click="showLogsModal = false" type="button"
                                class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


@push('scripts')
<script>
    function automationPageData() {
        return {
            showCreateModal: false,
            showEditModal: false,
            showRuleModal: false,
            showLogsModal: false,
            showMetricsModal: false,
            isSubmitting: false,
            logs: {},
            metrics: { trigger_count: 0, completion_rate: 0, failure_rate: 0, conversion: { opened: 0, accepted: 0, total_entities: 0 } },
            loadingLogs: false,
            loadingMetrics: false,
            editingRule: {
                id: null,
                name: '',
                trigger_event: '',
                condition_logic: 'AND',
                conditions: [],
                actions: [{type: 'email', to: '', field: '', value: '', delay: 0, retry_limit: 0, on_failure: 'continue', conditions: []}],
                settings: { is_enabled: true, rate_limit_count: null, rate_limit_period: 1440, max_executions_per_entity: null },
                is_active: true
            },
            openCreateModal() {
                this.editingRule = {
                    id: null,
                    name: '',
                    trigger_event: '',
                    condition_logic: 'AND',
                    conditions: [],
                    actions: [{type: 'email', to: '', field: '', value: '', delay: 0, retry_limit: 0, on_failure: 'continue', conditions: []}],
                    settings: { is_enabled: true, rate_limit_count: null, rate_limit_period: 1440, max_executions_per_entity: null },
                    is_active: true
                };
                this.showRuleModal = true;
            },
            addCondition(rule_or_action) {
                if (!rule_or_action.conditions) rule_or_action.conditions = [];
                rule_or_action.conditions.push({ type: 'payload', field: '', operator: '=', value: '' });
            },
            removeCondition(rule_or_action, index) {
                rule_or_action.conditions.splice(index, 1);
            },
            addAction(rule) {
                if (!rule.actions) rule.actions = [];
                rule.actions.push({ type: 'email', to: '', field: '', value: '', delay: 0, retry_limit: 0, on_failure: 'continue', conditions: [] });
            },
            removeAction(rule, index) {
                rule.actions.splice(index, 1);
            },
            editRule(rule) {
                this.editingRule = {
                    id: rule.id,
                    name: rule.name,
                    trigger_event: rule.triggers && rule.triggers.length ? rule.triggers[0].event_name : '',
                    condition_logic: rule.condition_logic,
                    settings: rule.settings || {},
                    is_active: !!rule.is_active,
                    conditions: (rule.global_conditions || []).map(c => ({
                        type: c.type,
                        field: c.field,
                        operator: c.operator,
                        value: c.value
                    })),
                    actions: (rule.steps || []).map(s => ({
                        type: s.action ? s.action.type : '',
                        delay: s.delay,
                        retry_limit: s.retry_limit || 0,
                        on_failure: s.on_failure || 'continue',
                        condition_logic: s.condition_logic,
                        conditions: (s.conditions || []).map(c => ({
                            type: c.type,
                            field: c.field,
                            operator: c.operator,
                            value: c.value
                        })),
                        config: s.action ? s.action.config : {}
                    }))
                };
                this.showRuleModal = true;
            },
            viewLogs(ruleId) {
                this.showLogsModal = true;
                this.loadingLogs = true;
                fetch(`{{ url('admin/automation') }}/${ruleId}/logs`)
                    .then(res => res.json())
                    .then(data => {
                        this.logs = data;
                        this.loadingLogs = false;
                    });
            },
            viewMetrics(ruleId) {
                this.showMetricsModal = true;
                this.loadingMetrics = true;
                fetch(`{{ url('admin/automation') }}/${ruleId}/metrics`)
                    .then(res => res.json())
                    .then(data => {
                        this.metrics = data;
                        this.loadingMetrics = false;
                    });
            }
        }
    }
</script>
@endpush
</x-app-layout>
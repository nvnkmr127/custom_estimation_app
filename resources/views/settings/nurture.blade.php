<x-app-layout>
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Advanced Nurturing Settings</h1>
            <p class="mt-2 text-sm text-slate-500">Configure automated follow-ups, hot lead alerts, and rescue offers.
            </p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            <a href="{{ route('settings.edit') }}" class="font-semibold text-indigo-600 hover:text-indigo-500">
                &larr; Back to Global Settings
            </a>
        </div>
    </div>

    <!-- Analytics Overview -->
    @if(isset($analytics))
        <div class="mt-8">
            <h3 class="text-base font-semibold leading-6 text-slate-900">Performance Analytics</h3>
            <dl class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-3">
                <div class="overflow-hidden rounded-xl bg-white px-4 py-5 shadow-sm ring-1 ring-slate-900/5 sm:p-6">
                    <dt class="truncate text-sm font-medium text-slate-500">Rescued Revenue</dt>
                    <dd class="mt-2 text-3xl font-semibold tracking-tight text-indigo-600">
                        {{ number_format($analytics['generatedRevenue'], 2) }}</dd>
                    <dd class="mt-1 text-xs text-slate-500">From accepted nurtured estimates</dd>
                </div>
                <div class="overflow-hidden rounded-xl bg-white px-4 py-5 shadow-sm ring-1 ring-slate-900/5 sm:p-6">
                    <dt class="truncate text-sm font-medium text-slate-500">Nurtured Estimates</dt>
                    <dd class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{{ $analytics['totalNurtured'] }}
                    </dd>
                    <dd class="mt-1 text-xs text-slate-500">Total estimates engaged</dd>
                </div>
                <div class="overflow-hidden rounded-xl bg-white px-4 py-5 shadow-sm ring-1 ring-slate-900/5 sm:p-6">
                    <dt class="truncate text-sm font-medium text-slate-500">Conversion Rate</dt>
                    <dd class="mt-2 text-3xl font-semibold tracking-tight text-green-600">
                        {{ $analytics['conversionRate'] }}%</dd>
                    <dd class="mt-1 text-xs text-green-600">Nurture success rate</dd>
                </div>
            </dl>
        </div>
    @endif

    <!-- Global Settings -->
    <div class="mt-8 bg-white shadow-sm ring-1 ring-slate-900/5 sm:rounded-xl">
        <div class="px-4 py-6 sm:p-8">
            <h2 class="text-base font-semibold leading-7 text-slate-900">Global Configuration</h2>
            <form action="{{ route('settings.nurture.update') }}" method="POST"
                class="mt-6 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                @csrf

                <div class="sm:col-span-6 flex items-center gap-x-3">
                    <input type="checkbox" name="nurture_system_active" id="nurture_system_active" value="1" {{ $isNurtureActive ? 'checked' : '' }}
                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                    <label for="nurture_system_active" class="text-sm font-medium leading-6 text-slate-900">Enable
                        Nurture System</label>
                </div>

                <div class="sm:col-span-3">
                    <label for="nurture_hot_lead_threshold"
                        class="block text-sm font-medium leading-6 text-slate-900">Hot Lead Threshold
                        (Views/Hour)</label>
                    <input type="number" name="nurture_hot_lead_threshold" id="nurture_hot_lead_threshold"
                        value="{{ $hotLeadThreshold }}"
                        class="mt-2 block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                    <p class="mt-1 text-xs text-slate-500">Alert staff if a client views an estimate this many times in
                        one hour.</p>
                </div>

                <div class="sm:col-span-3">
                    <label for="nurture_rescue_discount_limit"
                        class="block text-sm font-medium leading-6 text-slate-900">Max Rescue Discount (%)</label>
                    <input type="number" name="nurture_rescue_discount_limit" id="nurture_rescue_discount_limit"
                        value="{{ $rescueDiscountLimit }}"
                        class="mt-2 block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                    <p class="mt-1 text-xs text-slate-500">Maximum percentage discount the system can auto-offer.</p>
                </div>

                <div class="sm:col-span-6">
                    <button type="submit"
                        class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Save
                        Global Settings</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Rules List -->
    <div class="mt-8" x-data="{ showCreateModal: false, showEditModal: false, editingRule: {} }">
        <div class="sm:flex sm:items-center sm:justify-between">
            <h2 class="text-lg font-medium leading-6 text-slate-900">Nurture Rules</h2>
            <button @click="showCreateModal = true" type="button"
                class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                Add New Rule
            </button>
        </div>

        <div class="mt-4 flow-root">
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <table class="min-w-full divide-y divide-slate-300">
                        <thead>
                            <tr>
                                <th scope="col"
                                    class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0">Name
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">
                                    Trigger</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">
                                    Condition</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">
                                    Action</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">
                                    Status</th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @forelse($rules as $rule)
                                <tr>
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-0">
                                        {{ $rule->name }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                        <span
                                            class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">{{ $rule->trigger_type }}</span>
                                        <span class="text-xs ml-1">+{{ $rule->trigger_days }} days</span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                        <code
                                            class="text-xs bg-slate-100 p-1 rounded">{{ json_encode($rule->condition_logic) }}</code>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                        <span
                                            class="inline-flex items-center rounded-md bg-purple-50 px-2 py-1 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-700/10">{{ $rule->action_type }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                        @if($rule->is_active)
                                            <span
                                                class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Active</span>
                                        @else
                                            <span
                                                class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10">Inactive</span>
                                        @endif
                                    </td>
                                    <td
                                        class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                                        <button @click="editingRule = {{ $rule }}; showEditModal = true"
                                            class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</button>
                                        <form action="{{ route('settings.nurture.rules.destroy', $rule) }}" method="POST"
                                            class="inline" onsubmit="return confirm('Are you sure?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-4 text-center text-sm text-slate-500">No nurture rules defined
                                        yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Create Modal Overlay -->
        <div x-show="showCreateModal" class="relative z-10" aria-labelledby="modal-title" role="dialog"
            aria-modal="true" style="display: none;">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div
                        class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                        <form action="{{ route('settings.nurture.rules.store') }}" method="POST">
                            @csrf
                            <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                                <h3 class="text-base font-semibold leading-6 text-gray-900" id="modal-title">Create New
                                    Nurture Rule</h3>
                                <div class="mt-4 grid grid-cols-1 gap-y-4">
                                    <div>
                                        <label class="block text-sm font-medium leading-6 text-gray-900">Name</label>
                                        <input type="text" name="name" required
                                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium leading-6 text-gray-900">Trigger
                                            Type</label>
                                        <select name="trigger_type"
                                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                            <option value="time_based">Time Based (Days after sent)</option>
                                            <option value="engagement_based">Engagement Based (Days after last view)
                                            </option>
                                            <option value="expiry_based">Expiry Based (Days before expiry)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium leading-6 text-gray-900">Days
                                            Offset</label>
                                        <input type="number" name="trigger_days" value="3" required
                                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium leading-6 text-gray-900">Action
                                            Type</label>
                                        <select name="action_type"
                                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                            <option value="email">Send Email</option>
                                            <option value="alert_staff">Alert Staff</option>
                                            <option value="auto_discount">Auto Discount (Rescue)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                <button type="submit"
                                    class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:ml-3 sm:w-auto">Create</button>
                                <button @click="showCreateModal = false" type="button"
                                    class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
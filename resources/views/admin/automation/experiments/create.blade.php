<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Create New Experiment') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="experimentWizard()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">

                    <form action="{{ route('automation.experiments.store') }}" method="POST">
                        @csrf

                        <!-- Step 1: Basics -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">1. Experiment Details</h3>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Experiment Name</label>
                                    <input type="text" name="name" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Trigger Event</label>
                                    <select name="trigger_event" x-model="selectedEvent" @change="fetchAutomations()"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="">Select Event...</option>
                                        @foreach($events as $event)
                                            <option value="{{ $event }}">{{ $event }}</option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500">Only one experiment can be active per event.
                                    </p>
                                </div>
                                
                                <div class="col-span-2 sm:col-span-1">
                                    <label class="block text-sm font-medium text-gray-700">Goal Event (Metric)</label>
                                    <select name="goal_event" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="">Select Goal...</option>
                                        <option value="estimate.accepted">Estimate Accepted</option>
                                        <option value="estimate.viewed">Estimate Viewed</option>
                                        <option value="invoice.paid">Invoice Paid</option>
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500">The event that counts as a "Conversion".</p>
                                </div>

                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-gray-700">Description
                                        (Optional)</label>
                                    <textarea name="description" rows="3"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                                </div>
                            </div>
                        </div>

                        <hr class="my-8">

                        <!-- Step 2: Variants -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">2. Configure Variants</h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <!-- Variant A -->
                                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                    <h4 class="font-bold text-gray-700 mb-4">Variant A (Control)</h4>

                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700">Automation Rule</label>
                                        <select name="variants[0][automation_id]"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option value="">Select Rule...</option>
                                            <template x-for="rule in automations">
                                                <option :value="rule.id" x-text="rule.name"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Traffic Weight
                                            (%)</label>
                                        <input type="number" name="variants[0][weight]" x-model="weightA" min="0"
                                            max="100"
                                            class="mt-1 block w-24 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                </div>

                                <!-- Variant B -->
                                <div class="bg-indigo-50 p-4 rounded-lg border border-indigo-200">
                                    <h4 class="font-bold text-indigo-700 mb-4">Variant B (Test)</h4>

                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700">Automation Rule</label>
                                        <select name="variants[1][automation_id]"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option value="">Select Rule...</option>
                                            <template x-for="rule in automations">
                                                <option :value="rule.id" x-text="rule.name"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Traffic Weight
                                            (%)</label>
                                        <input type="number" name="variants[1][weight]" :value="100 - weightA" readonly
                                            class="bg-gray-100 mt-1 block w-24 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm cursor-not-allowed">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <a href="{{ route('automation.experiments.index') }}"
                                class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 mr-3">
                                Cancel
                            </a>
                            <button type="submit"
                                class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Create Experiment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function experimentWizard() {
            return {
                selectedEvent: '',
                weightA: 50,
                automations: [],

                fetchAutomations() {
                    if (!this.selectedEvent) {
                        this.automations = [];
                        return;
                    }

                    // AutomationController@index returns JSON (id, name) filtered by trigger
                    // when the request asks for JSON via the Accept header.
                    fetch('/admin/automation?trigger=' + encodeURIComponent(this.selectedEvent), {
                        headers: { 'Accept': 'application/json' }
                    })
                        .then(res => {
                            if (!res.ok) throw new Error('Failed to load automations');
                            return res.json();
                        })
                        .then(data => {
                            this.automations = data;
                        })
                        .catch(err => {
                            console.error('Failed to fetch automations for trigger', err);
                            this.automations = [];
                        });
                }
            }
        }
    </script>
</x-app-layout>
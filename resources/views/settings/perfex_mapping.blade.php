<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Perfex CRM Field Mapping') }}
            </h2>
            <a href="{{ route('settings.edit') }}" class="text-sm text-gray-600 hover:text-gray-900">Back to
                Settings</a>
        </div>
    </x-slot>

    <div class="py-12" x-data="fieldMapping()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- API Test Section -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">1. Test & Inspect Data</h3>
                    <p class="text-sm text-gray-500 mb-4">Click below to fetch the latest Lead data from Perfex. Use the
                        raw JSON response to find the exact field names (keys) you need to map.</p>

                    <button type="button" @click="fetchRawData" :disabled="isLoading"
                        class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                        <span x-text="isLoading ? 'Fetching...' : 'Fetch Raw Data sample'"></span>
                    </button>

                    <div x-show="rawData" class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Raw API Response (Latest
                            Lead):</label>
                        <div class="bg-gray-50 p-4 rounded-md overflow-x-auto border border-gray-200">
                            <pre class="text-xs text-gray-800 font-mono"
                                x-text="JSON.stringify(rawData, null, 2)"></pre>
                        </div>
                    </div>
                    <div x-show="errorMessage" class="mt-4 p-4 bg-red-50 text-red-700 rounded-md">
                        <p x-text="errorMessage"></p>
                    </div>
                </div>
            </div>

            <!-- Mapping Section -->
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            Configure how Perfex CRM fields map to your Client system.
                            <br>
                            - <strong>Perfex Field:</strong> Enter the exact name of the field (or Custom Field Label).
                            <br>
                            - <strong>System Field:</strong> Select the destination column in this system.
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">2. Configure Field Mapping</h3>

                    <form action="{{ route('settings.perfex.mapping.update') }}" method="POST">
                        @csrf

                        <table class="min-w-full divide-y divide-gray-200 mb-6">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Perfex Field (API Key or Label)</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Maps To (System Field)</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="(row, index) in rows" :key="index">
                                    <tr>
                                        <td class="px-6 py-4">
                                            <input type="text" :name="'mappings['+index+'][perfex_field]'"
                                                x-model="row.perfex_field"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono"
                                                placeholder="e.g. phonenumber">
                                        </td>
                                        <td class="px-6 py-4">
                                            <select :name="'mappings['+index+'][local_field]'" x-model="row.local_field"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                <option value="">Select Field</option>
                                                @foreach($clientColumns as $key => $label)
                                                    <option value="{{ $key }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="px-6 py-4">
                                            <button type="button" @click="removeRow(index)"
                                                class="text-red-600 hover:text-red-900">Remove</button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>

                        <div class="flex items-center justify-between">
                            <button type="button" @click="addRow()"
                                class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 active:bg-gray-400 focus:outline-none focus:border-gray-500 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                                + Add Field
                            </button>

                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-800 focus:outline-none focus:border-indigo-700 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                                Save Mappings
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function fieldMapping() {
            return {
                rows: @json($mapping ?? [['perfex_field' => '', 'local_field' => '']]),
                isLoading: false,
                rawData: null,
                errorMessage: null,
                addRow() {
                    this.rows.push({ perfex_field: '', local_field: '' });
                },
                removeRow(index) {
                    this.rows.splice(index, 1);
                },
                fetchRawData() {
                    this.isLoading = true;
                    this.errorMessage = null;
                    this.rawData = null;

                    fetch('{{ route('settings.perfex.test') }}')
                        .then(response => response.json())
                        .then(data => {
                            this.isLoading = false;
                            if (data.success) {
                                this.rawData = data.raw_sample;
                            } else {
                                this.errorMessage = data.message;
                            }
                        })
                        .catch(error => {
                            this.isLoading = false;
                            this.errorMessage = 'Request failed: ' + error;
                        });
                }
            }
        }
    </script>
</x-app-layout>
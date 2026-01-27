<x-app-layout>
    <div class="sm:flex sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Perfex CRM Leads</h1>
            <p class="mt-2 text-sm text-slate-500">Live data from your connected Perfex CRM.</p>
        </div>
        <div>
            <!-- Reload Button -->
            <a href="{{ route('perfex.index') }}"
                class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Refresh
                Data</a>
        </div>
    </div>

    @if(empty(config('services.perfex.url')))
        <div class="rounded-md bg-yellow-50 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd"
                            d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">Configuration Missing</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p>Please add PERFEX_API_URL and PERFEX_API_TOKEN to your .env file.</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-900/5 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Property
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @if(isset($leads) && is_array($leads) && count($leads) > 0)
                    @foreach($leads as $lead)
                        @if(!is_array($lead)) @continue @endif
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                #{{ $lead['id'] ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $lead['name'] ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $lead['company'] ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span
                                    class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                                    {{ $lead['status_name'] ?? ($lead['status'] ?? 'Unknown') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div class="font-medium text-slate-900">{{ $lead['property_name'] ?? '-' }}</div>
                                <div class="text-xs text-slate-500">{{ $lead['property_address'] ?? '' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $lead['email'] ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <form action="{{ route('perfex.import') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $lead['id'] }}">
                                    <!-- Fallback Data -->
                                    <input type="hidden" name="name" value="{{ $lead['name'] ?? '' }}">
                                    <input type="hidden" name="company" value="{{ $lead['company'] ?? '' }}">
                                    <input type="hidden" name="email" value="{{ $lead['email'] ?? '' }}">
                                    <input type="hidden" name="property_name" value="{{ $lead['property_name'] ?? '' }}">
                                    <input type="hidden" name="property_address" value="{{ $lead['property_address'] ?? '' }}">
                                    <button type="submit"
                                        class="text-indigo-600 hover:text-indigo-900 font-semibold">Import</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                            No leads found or API connection failed.
                            @if(isset($leads['error']))
                                <br><span class="text-red-500">Error: {{ $leads['error'] }}</span>
                            @endif
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</x-app-layout>
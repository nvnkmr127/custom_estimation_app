<x-app-layout>
    <div class="sm:flex sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $client->name }}</h1>
            <p class="mt-2 text-sm text-slate-500">{{ $client->company }}</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 flex gap-x-3">
            <a href="{{ route('clients.edit', $client) }}"
                class="block rounded-lg bg-indigo-600 px-4 py-2.5 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                Edit Client
            </a>
            <a href="{{ route('clients.index') }}"
                class="block rounded-lg bg-white px-4 py-2.5 text-center text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
                Back
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Client Details -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl p-6">
                <h2 class="text-base font-semibold leading-7 text-gray-900 mb-4">Contact Information</h2>
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $client->email ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Phone</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $client->phone ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Address</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            @if($client->address)
                                {{ $client->address }}<br>
                            @endif
                            @if($client->city || $client->state || $client->zip)
                                {{ $client->city }} {{ $client->state }} {{ $client->zip }}<br>
                            @endif
                            {{ $client->country }}
                        </dd>
                    </div>
                </dl>
            </div>

            @if($client->notes)
                <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl p-6">
                    <h2 class="text-base font-semibold leading-7 text-gray-900 mb-4">Notes</h2>
                    <div class="text-sm text-gray-600 whitespace-pre-wrap">{{ $client->notes }}</div>
                </div>
            @endif
        </div>

        <!-- Recent Estimates -->
        <div class="lg:col-span-2">
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl overflow-hidden">
                <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
                    <h3 class="text-base font-semibold leading-6 text-gray-900">Recent Estimates</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Number</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status</th>
                                <th scope="col"
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Total</th>
                                <th scope="col" class="relative px-6 py-3">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($client->estimates as $estimate)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-indigo-600">
                                        <a href="{{ route('estimates.show', $estimate) }}" class="hover:underline">
                                            {{ $estimate->estimate_number }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $estimate->estimate_date->format('M d, Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset 
                                                    @if($estimate->status === 'accepted') bg-green-50 text-green-700 ring-green-600/20
                                                    @elseif($estimate->status === 'declined') bg-red-50 text-red-700 ring-red-600/10
                                                    @elseif($estimate->status === 'sent') bg-blue-50 text-blue-700 ring-blue-700/10
                                                    @else bg-gray-50 text-gray-600 ring-gray-500/10
                                                    @endif">
                                            {{ ucfirst($estimate->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
                                        {{ $estimate->currency_symbol }} {{ number_format($estimate->grand_total, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('estimates.show', $estimate) }}"
                                            class="text-indigo-600 hover:text-indigo-900">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                        No recent estimates found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Analytics: Estimate #') . $estimate->estimate_number }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 flex justify-between items-center">
                <a href="{{ route('estimates.edit', $estimate) }}" class="text-indigo-600 hover:text-indigo-900">&larr;
                    Back to Estimate</a>
                <a href="{{ route('estimates.analytics.export', $estimate) }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                    Export to CSV
                </a>
            </div>

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center">
                    <dt class="text-sm font-medium text-gray-500 truncate">Total Views (Online)</dt>
                    <dd class="mt-1 text-3xl font-semibold text-indigo-600">{{ $stats['views'] }}</dd>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center">
                    <dt class="text-sm font-medium text-gray-500 truncate">Total Downloads (PDF)</dt>
                    <dd class="mt-1 text-3xl font-semibold text-green-600">{{ $stats['downloads'] }}</dd>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center">
                    <dt class="text-sm font-medium text-gray-500 truncate">Unique Viewers</dt>
                    <dd class="mt-1 text-3xl font-semibold text-blue-600">{{ $stats['unique_viewers'] }}</dd>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Activity Over Time Chart -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Activity (Last 7 Days)</h3>
                    <div class="relative h-64">
                        <canvas id="activityChart"></canvas>
                    </div>
                </div>

                <!-- Device Breakdown -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Device Breakdown</h3>
                    <div class="relative h-64">
                        <canvas id="deviceChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Detailed Logs -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Detailed Access Log</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date/Time</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Action</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Location</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Device</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Browser</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($analytics as $log)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $log->created_at->format('M d, Y h:i A') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $log->action === 'view' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                            {{ ucfirst($log->action) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $log->location_json ? ($log->location_json['city'] . ', ' . $log->location_json['country']) : 'Unknown' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $log->device }}
                                        ({{ $log->platform }})</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $log->browser }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">No
                                        activity recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Activity Chart
                const ctxActivity = document.getElementById('activityChart').getContext('2d');
                new Chart(ctxActivity, {
                    type: 'line',
                    data: {
                        labels: @json($chartData['labels']),
                        datasets: [{
                            label: 'Views',
                            data: @json($chartData['views']),
                            borderColor: 'rgb(79, 70, 229)',
                            tension: 0.1
                        }, {
                            label: 'Downloads',
                            data: @json($chartData['downloads']),
                            borderColor: 'rgb(16, 185, 129)',
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                    }
                });

                // Device Chart
                const ctxDevice = document.getElementById('deviceChart').getContext('2d');
                const deviceData = @json($deviceStats); // {Mobile: 5, Desktop: 10}

                new Chart(ctxDevice, {
                    type: 'doughnut',
                    data: {
                        labels: Object.keys(deviceData),
                        datasets: [{
                            data: Object.values(deviceData),
                            backgroundColor: [
                                'rgb(59, 130, 246)',
                                'rgb(16, 185, 129)',
                                'rgb(245, 158, 11)',
                                'rgb(239, 68, 68)'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            });
        </script>
    @endpush
</x-app-layout>
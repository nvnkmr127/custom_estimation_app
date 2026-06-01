<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Analytics &mdash; Estimate #{{ $estimate->estimate_number }}
            </h2>
            <div class="flex items-center gap-3">
                {{-- Time Range Selector --}}
                <div class="flex rounded-md shadow-sm" role="group">
                    @foreach([7 => '7d', 30 => '30d', 90 => '90d'] as $days => $label)
                        <a href="{{ route('estimates.analytics', ['estimate' => $estimate, 'range' => $days]) }}"
                           class="px-3 py-1.5 text-xs font-medium border {{ $range === $days ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }} {{ $days === 7 ? 'rounded-l-md' : ($days === 90 ? 'rounded-r-md' : '') }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
                <a href="{{ route('estimates.analytics.export', $estimate) }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition ease-in-out duration-150">
                    Export CSV
                </a>
                <a href="{{ route('estimates.show', $estimate) }}" class="text-sm text-indigo-600 hover:text-indigo-900">&larr; Back</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- ── Stats Overview ── --}}
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="bg-white rounded-lg shadow-sm p-5 text-center">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Views</p>
                    <p class="mt-1 text-3xl font-bold text-indigo-600">{{ $stats['views'] }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-5 text-center">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">PDF Downloads</p>
                    <p class="mt-1 text-3xl font-bold text-green-600">{{ $stats['downloads'] }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-5 text-center">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Unique Viewers</p>
                    <p class="mt-1 text-3xl font-bold text-blue-600">{{ $stats['unique_viewers'] }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-5 text-center">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Download Rate</p>
                    <p class="mt-1 text-3xl font-bold text-amber-600">{{ $stats['conversion_rate'] }}%</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-5 text-center">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Last Viewed</p>
                    <p class="mt-1 text-sm font-semibold text-gray-700">
                        {{ $stats['last_viewed_at'] ? $stats['last_viewed_at']->diffForHumans() : '—' }}
                    </p>
                </div>
            </div>

            {{-- ── Activity + Device charts ── --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4 uppercase tracking-wide">
                        Activity — Last {{ $range }} Days
                    </h3>
                    @if($stats['views'] === 0 && $stats['downloads'] === 0)
                        <div class="flex items-center justify-center h-56 text-gray-400 text-sm">No activity yet.</div>
                    @else
                        <div class="relative h-56"><canvas id="activityChart"></canvas></div>
                    @endif
                </div>
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4 uppercase tracking-wide">Device Breakdown</h3>
                    @if($deviceStats->isEmpty())
                        <div class="flex items-center justify-center h-56 text-gray-400 text-sm">No data.</div>
                    @else
                        <div class="relative h-56"><canvas id="deviceChart"></canvas></div>
                    @endif
                </div>
            </div>

            {{-- ── Browser + Location ── --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4 uppercase tracking-wide">Top Browsers</h3>
                    @if($browserStats->isEmpty())
                        <div class="text-gray-400 text-sm">No data.</div>
                    @else
                        <div class="space-y-2">
                            @php $maxBrowser = $browserStats->max(); @endphp
                            @foreach($browserStats as $browser => $count)
                                <div class="flex items-center gap-3">
                                    <span class="w-24 text-sm text-gray-600 truncate">{{ $browser }}</span>
                                    <div class="flex-1 bg-gray-100 rounded-full h-2">
                                        <div class="bg-indigo-500 h-2 rounded-full" style="width: {{ $maxBrowser > 0 ? round(($count / $maxBrowser) * 100) : 0 }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-500 w-6 text-right">{{ $count }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4 uppercase tracking-wide">Top Countries</h3>
                    @if($locationStats->isEmpty())
                        <div class="text-gray-400 text-sm">No location data.</div>
                    @else
                        <div class="space-y-2">
                            @php $maxLoc = $locationStats->max(); @endphp
                            @foreach($locationStats as $country => $count)
                                <div class="flex items-center gap-3">
                                    <span class="w-28 text-sm text-gray-600 truncate">{{ $country }}</span>
                                    <div class="flex-1 bg-gray-100 rounded-full h-2">
                                        <div class="bg-green-500 h-2 rounded-full" style="width: {{ $maxLoc > 0 ? round(($count / $maxLoc) * 100) : 0 }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-500 w-6 text-right">{{ $count }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- ── Hourly Heatmap ── --}}
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-4 uppercase tracking-wide">Views by Hour of Day</h3>
                @if($stats['views'] === 0)
                    <div class="text-gray-400 text-sm">No data.</div>
                @else
                    <div class="flex items-end gap-1 h-20">
                        @php $maxHour = $heatmapData->max() ?: 1; @endphp
                        @foreach($heatmapData as $hour => $count)
                            <div class="flex-1 flex flex-col items-center gap-1 group relative">
                                <div class="w-full rounded-t transition-all"
                                     style="height: {{ max(4, round(($count / $maxHour) * 64)) }}px; background-color: rgba(79,70,229,{{ $maxHour > 0 ? round($count / $maxHour, 2) : 0 }});"
                                     title="{{ $hour }}:00 — {{ $count }} view(s)"></div>
                                <span class="text-xs text-gray-400 {{ $hour % 6 === 0 ? '' : 'invisible' }}">{{ $hour }}h</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- ── Detailed Access Log ── --}}
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Detailed Access Log</h3>
                    <span class="text-xs text-gray-400">{{ $logs->total() }} total records</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date / Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Device</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Browser</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unique</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($logs as $log)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 whitespace-nowrap text-gray-500">
                                        {{ $log->created_at->format('M d, Y') }}
                                        <span class="text-gray-400">{{ $log->created_at->format('H:i') }}</span>
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap">
                                        <span class="px-2 py-0.5 inline-flex text-xs font-semibold rounded-full
                                            {{ $log->action === 'view' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                            {{ ucfirst($log->action) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-gray-500">
                                        @if($log->location_json)
                                            {{ $log->location_json['city'] ?? '—' }}, {{ $log->location_json['country'] ?? '—' }}
                                        @else
                                            <span class="text-gray-400">Unknown</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-gray-500">
                                        {{ $log->device ?? '—' }}
                                        @if($log->platform)
                                            <span class="text-gray-400">({{ $log->platform }})</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 whitespace-nowrap text-gray-500">{{ $log->browser ?? '—' }}</td>
                                    <td class="px-6 py-3 whitespace-nowrap">
                                        @if($log->is_unique)
                                            <span class="text-green-600 font-medium text-xs">Yes</span>
                                        @else
                                            <span class="text-gray-400 text-xs">No</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-gray-400">No activity recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($logs->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const activityCanvas = document.getElementById('activityChart');
                const deviceCanvas   = document.getElementById('deviceChart');

                if (activityCanvas) {
                    new Chart(activityCanvas.getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: @json($chartData['labels']),
                            datasets: [
                                {
                                    label: 'Views',
                                    data: @json($chartData['views']),
                                    borderColor: 'rgb(79, 70, 229)',
                                    backgroundColor: 'rgba(79, 70, 229, 0.08)',
                                    tension: 0.3,
                                    fill: true,
                                    pointRadius: 3,
                                },
                                {
                                    label: 'Downloads',
                                    data: @json($chartData['downloads']),
                                    borderColor: 'rgb(16, 185, 129)',
                                    backgroundColor: 'rgba(16, 185, 129, 0.08)',
                                    tension: 0.3,
                                    fill: true,
                                    pointRadius: 3,
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: { mode: 'index', intersect: false },
                            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
                            scales: {
                                y: { beginAtZero: true, ticks: { precision: 0, font: { size: 11 } } },
                                x: { ticks: { font: { size: 11 } } }
                            }
                        }
                    });
                }

                if (deviceCanvas) {
                    const deviceData = @json($deviceStats);
                    new Chart(deviceCanvas.getContext('2d'), {
                        type: 'doughnut',
                        data: {
                            labels: Object.keys(deviceData),
                            datasets: [{
                                data: Object.values(deviceData),
                                backgroundColor: [
                                    'rgb(59, 130, 246)',
                                    'rgb(16, 185, 129)',
                                    'rgb(245, 158, 11)',
                                    'rgb(239, 68, 68)',
                                    'rgb(139, 92, 246)'
                                ],
                                borderWidth: 2,
                                borderColor: '#fff'
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
                            }
                        }
                    });
                }
            });
        </script>
    @endpush
</x-app-layout>

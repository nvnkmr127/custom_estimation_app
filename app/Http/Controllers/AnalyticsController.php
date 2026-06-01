<?php

namespace App\Http\Controllers;

use App\Models\Estimate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function dashboard(Estimate $estimate, Request $request)
    {
        $this->authorize('view', $estimate);

        // Time range: 7, 30, or 90 days (default 7)
        $range = in_array((int) $request->get('range'), [7, 30, 90]) ? (int) $request->get('range') : 7;

        $analytics = $estimate->analytics()
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = [
            'views'           => $analytics->where('action', 'view')->count(),
            'downloads'       => $analytics->where('action', 'download')->count(),
            'unique_viewers'  => $analytics->where('action', 'view')->where('is_unique', true)->count(),
            'last_viewed_at'  => $analytics->where('action', 'view')->first()?->created_at,
            'conversion_rate' => $analytics->where('action', 'view')->count() > 0
                ? round(($analytics->where('action', 'download')->count() / $analytics->where('action', 'view')->count()) * 100, 1)
                : 0,
        ];

        // Chart Data: Views/Downloads over last N days
        $dates = collect();
        for ($i = $range - 1; $i >= 0; $i--) {
            $dates->push(now()->subDays($i)->format('Y-m-d'));
        }

        $dailyStats = $estimate->analytics()
            ->whereDate('created_at', '>=', now()->subDays($range - 1)->startOfDay())
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(CASE WHEN action = "view" THEN 1 ELSE 0 END) as views'),
                DB::raw('SUM(CASE WHEN action = "download" THEN 1 ELSE 0 END) as downloads')
            )
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $chartData = [
            'labels'    => $dates->map(fn($d) => \Carbon\Carbon::parse($d)->format($range > 7 ? 'M d' : 'D M d')),
            'views'     => $dates->map(fn($d) => $dailyStats->get($d)?->views ?? 0)->values(),
            'downloads' => $dates->map(fn($d) => $dailyStats->get($d)?->downloads ?? 0)->values(),
        ];

        // Device breakdown (excluding nulls)
        $deviceStats = $estimate->analytics()
            ->whereNotNull('device')
            ->select('device', DB::raw('count(*) as count'))
            ->groupBy('device')
            ->orderBy('count', 'desc')
            ->pluck('count', 'device');

        // Browser breakdown (top 6)
        $browserStats = $estimate->analytics()
            ->whereNotNull('browser')
            ->select('browser', DB::raw('count(*) as count'))
            ->groupBy('browser')
            ->orderBy('count', 'desc')
            ->limit(6)
            ->pluck('count', 'browser');

        // Location breakdown (top 10 countries)
        $locationStats = $estimate->analytics()
            ->whereNotNull('location_json')
            ->get()
            ->groupBy(fn($a) => $a->location_json['country'] ?? 'Unknown')
            ->map->count()
            ->sortDesc()
            ->take(10);

        // Hourly heatmap — views by hour of day
        $hourlyStats = $estimate->analytics()
            ->where('action', 'view')
            ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('count(*) as count'))
            ->groupBy('hour')
            ->pluck('count', 'hour');

        $heatmapData = collect(range(0, 23))->map(fn($h) => $hourlyStats->get($h, 0));

        // Paginated detailed log (20 per page)
        $logs = $estimate->analytics()
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return view('estimates.analytics', compact(
            'estimate', 'analytics', 'stats', 'chartData',
            'deviceStats', 'browserStats', 'locationStats',
            'heatmapData', 'logs', 'range'
        ));
    }

    public function export(Estimate $estimate)
    {
        $this->authorize('view', $estimate);

        $fileName = 'analytics_' . $estimate->estimate_number . '_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $columns = ['Date', 'Action', 'IP Address', 'Device', 'Platform', 'Browser', 'City', 'Country', 'Unique'];

        $callback = function () use ($estimate, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            $estimate->analytics()
                ->orderBy('created_at', 'desc')
                ->chunk(500, function ($records) use ($file) {
                    foreach ($records as $log) {
                        fputcsv($file, [
                            $log->created_at->format('Y-m-d H:i:s'),
                            ucfirst($log->action),
                            $log->ip_address ?? 'Unknown',
                            $log->device ?? 'Unknown',
                            $log->platform ?? 'Unknown',
                            $log->browser ?? 'Unknown',
                            $log->location_json['city'] ?? 'Unknown',
                            $log->location_json['country'] ?? 'Unknown',
                            $log->is_unique ? 'Yes' : 'No',
                        ]);
                    }
                });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Estimate;
use App\Models\EstimateAnalytic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Display analytics for a specific estimate.
     */
    public function dashboard(Estimate $estimate)
    {
        $this->authorize('view', $estimate);

        $analytics = $estimate->analytics()
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = [
            'views' => $analytics->where('action', 'view')->count(),
            'downloads' => $analytics->where('action', 'download')->count(),
            'unique_viewers' => $analytics->where('action', 'view')->where('is_unique', true)->count(),
        ];

        // Chart Data: Views/Downloads over last 7 days
        $dates = collect();
        for ($i = 6; $i >= 0; $i--) {
            $dates->push(now()->subDays($i)->format('Y-m-d'));
        }

        $chartData = [
            'labels' => $dates->map(function ($date) {
                return \Carbon\Carbon::parse($date)->format('M d');
            }),
            'views' => [],
            'downloads' => []
        ];

        foreach ($dates as $date) {
            $chartData['views'][] = $estimate->analytics()
                ->where('action', 'view')
                ->whereDate('created_at', $date)
                ->count();

            $chartData['downloads'][] = $estimate->analytics()
                ->where('action', 'download')
                ->whereDate('created_at', $date)
                ->count();
        }

        // Action Breakdown (Device)
        $deviceStats = $estimate->analytics()
            ->select('device', DB::raw('count(*) as count'))
            ->groupBy('device')
            ->pluck('count', 'device');

        return view('estimates.analytics', compact('estimate', 'analytics', 'stats', 'chartData', 'deviceStats'));
    }

    /**
     * Export analytics to CSV.
     */
    public function export(Estimate $estimate)
    {
        $this->authorize('view', $estimate);

        $fileName = 'analytics_' . $estimate->estimate_number . '_' . date('Y-m-d') . '.csv';

        $analytics = $estimate->analytics()->orderBy('created_at', 'desc')->get();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = ['Date', 'Action', 'IP Address', 'Device', 'Browser', 'Location'];

        $callback = function () use ($analytics, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($analytics as $log) {
                $location = $log->location_json ? ($log->location_json['city'] . ', ' . $log->location_json['country']) : 'Unknown';
                fputcsv($file, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    ucfirst($log->action),
                    $log->ip_address,
                    $log->device,
                    $log->browser,
                    $location
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Estimate;
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
            'downloads' => [],
        ];

        $dailyStats = $estimate->analytics()
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(CASE WHEN action = "view" THEN 1 ELSE 0 END) as views'),
                DB::raw('SUM(CASE WHEN action = "download" THEN 1 ELSE 0 END) as downloads')
            )
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        foreach ($dates as $date) {
            $statsForDate = $dailyStats->get($date);
            $chartData['views'][] = $statsForDate ? $statsForDate->views : 0;
            $chartData['downloads'][] = $statsForDate ? $statsForDate->downloads : 0;
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

        $fileName = 'analytics_'.$estimate->estimate_number.'_'.date('Y-m-d').'.csv';

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
                $location = $log->location_json ? ($log->location_json['city'].', '.$log->location_json['country']) : 'Unknown';
                fputcsv($file, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    ucfirst($log->action),
                    $log->ip_address,
                    $log->device,
                    $log->browser,
                    $location,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

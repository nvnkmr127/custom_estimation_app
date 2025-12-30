<?php

namespace App\Http\Controllers;

use App\Models\Estimate;

class ReportController extends Controller
{
    public function index()
    {
        // 1. Key Metrics
        $totalEstimates = Estimate::count();
        $acceptedEstimates = Estimate::where('status', 'accepted')->count();
        $declinedEstimates = Estimate::where('status', 'declined')->count();
        $totalStats = $acceptedEstimates + $declinedEstimates;
        $winRate = $totalStats > 0 ? round(($acceptedEstimates / $totalStats) * 100, 1) : 0;

        $totalRevenue = Estimate::where('status', 'accepted')->sum('grand_total');
        $pipelineValue = Estimate::whereIn('status', ['draft', 'waiting_approval', 'sent'])->sum('grand_total');

        // 1.1 Funnel Analysis
        $sentCount = Estimate::whereIn('status', ['sent', 'accepted', 'declined', 'expired'])->count();
        $openedCount = Estimate::whereNotNull('email_opened_at')->count();
        $viewedCount = Estimate::where('view_count', '>', 0)->count();
        $finalAccepted = Estimate::where('status', 'accepted')->count();

        $funnel = [
            'sent' => $sentCount,
            'opened' => $openedCount,
            'viewed' => $viewedCount,
            'accepted' => $finalAccepted,
            'rates' => [
                'open_rate' => $sentCount > 0 ? round(($openedCount / $sentCount) * 100, 1) : 0,
                'view_rate' => $openedCount > 0 ? round(($viewedCount / $openedCount) * 100, 1) : 0,
                'conversion_rate' => $viewedCount > 0 ? round(($finalAccepted / $viewedCount) * 100, 1) : 0,
            ],
        ];

        // 1.2 Revenue Forecast (Weighted by probability)
        $weightedForecast = Estimate::whereIn('status', ['sent', 'waiting_approval'])
            ->selectRaw('SUM(grand_total * 0.7) as weighted_total') // Assuming 70% probability for sent/waiting
            ->first()->weighted_total ?? 0;

        // 2. Chart Data (Last 6 Months Trend)
        $chartLabels = [];
        $chartData = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $chartLabels[] = $date->format('M Y');

            // Sum of accepted estimates in that month
            $val = Estimate::where('status', 'accepted')
                ->whereYear('estimate_date', $date->year)
                ->whereMonth('estimate_date', $date->month)
                ->sum('grand_total');

            $chartData[] = $val;
        }

        // 3. Top Products (Simplified)
        $topProducts = \Illuminate\Support\Facades\DB::table('estimate_items')
            ->select('name', \Illuminate\Support\Facades\DB::raw('count(*) as count'), \Illuminate\Support\Facades\DB::raw('sum(total) as revenue'))
            ->groupBy('name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        return view('reports.index', compact(
            'totalEstimates',
            'acceptedEstimates',
            'declinedEstimates',
            'winRate',
            'totalRevenue',
            'pipelineValue',
            'chartLabels',
            'chartData',
            'topProducts',
            'funnel',
            'weightedForecast'
        ));
    }
}

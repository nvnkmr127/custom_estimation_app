<?php

namespace App\Http\Controllers;

use App\Models\Estimate;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        // 1. Key Metrics
        $totalEstimates = Estimate::count();
        $acceptedEstimates = Estimate::where('estimate_status', Estimate::EST_STATUS_ACCEPTED)->count();
        $declinedEstimates = Estimate::where('estimate_status', Estimate::EST_STATUS_DECLINED)->count();
        $totalStats = $acceptedEstimates + $declinedEstimates;
        $winRate = $totalStats > 0 ? round(($acceptedEstimates / $totalStats) * 100, 1) : 0;

        $totalRevenue = Estimate::where('estimate_status', Estimate::EST_STATUS_ACCEPTED)->sum('grand_total');
        $pipelineValue = Estimate::whereIn('estimate_status', [Estimate::EST_STATUS_DRAFT, Estimate::EST_STATUS_SENT, Estimate::EST_STATUS_PENDING_APPROVAL, Estimate::EST_STATUS_APPROVED])->sum('grand_total');

        // 1.1 Funnel Analysis
        $sentCount = Estimate::whereIn('estimate_status', [Estimate::EST_STATUS_SENT, Estimate::EST_STATUS_ACCEPTED, Estimate::EST_STATUS_DECLINED, Estimate::EST_STATUS_EXPIRED])->count();
        $openedCount = Estimate::whereNotNull('email_opened_at')->count();
        $viewedCount = Estimate::where('view_count', '>', 0)->count();
        $finalAccepted = Estimate::where('estimate_status', Estimate::EST_STATUS_ACCEPTED)->count();

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
        $weightedResult = Estimate::whereIn('estimate_status', [Estimate::EST_STATUS_SENT, Estimate::EST_STATUS_PENDING_APPROVAL])
            ->selectRaw('SUM(grand_total * 0.7) as weighted_total') // Assuming 70% probability for sent/waiting
            ->first();
        $weightedForecast = $weightedResult ? $weightedResult->weighted_total : 0;

        // 2. Chart Data (Last 6 Months Trend) - Optimized Single Query
        $sixMonthsAgo = now()->subMonths(5)->startOfMonth();

        $monthLabelExpr = match (DB::connection()->getDriverName()) {
            'mysql', 'mariadb' => "DATE_FORMAT(estimate_date, '%m %Y')",
            'pgsql' => "to_char(estimate_date, 'MM YYYY')",
            default => "strftime('%m %Y', estimate_date)",
        };
        
        $monthlyRevenue = Estimate::where('estimate_status', Estimate::EST_STATUS_ACCEPTED)
            ->where('estimate_date', '>=', $sixMonthsAgo)
            ->selectRaw("$monthLabelExpr as month_label, SUM(grand_total) as revenue")
            ->groupBy('month_label')
            ->pluck('revenue', 'month_label');

        $chartLabels = [];
        $chartData = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $chartLabels[] = $date->format('M Y');
            
            // Map labels appropriately (handling SQLite strftime format e.g. "04 2026")
            $lookupKey = $date->format('m Y');
            $chartData[] = $monthlyRevenue[$lookupKey] ?? 0;
        }

        // 3. Top Products (Simplified)
        $topProducts = DB::table('estimate_items')
            ->select('name', DB::raw('count(*) as count'), DB::raw('sum(total) as revenue'))
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

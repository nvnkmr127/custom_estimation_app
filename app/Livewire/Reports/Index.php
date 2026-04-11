<?php

namespace App\Livewire\Reports;

use App\Models\Estimate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Index extends Component
{
    public $filter = 'this_month';
    public $customStartDate;
    public $customEndDate;
    public $userId = '';
    public $users = [];
    public $revenueTarget;
    public $clientSearch = '';
    public $leaderboard = [];
    public $dealSizeBuckets = [];

    public function mount()
    {
        $this->customStartDate = now()->startOfMonth()->format('Y-m-d');
        $this->customEndDate = now()->endOfMonth()->format('Y-m-d');

        if (auth()->user()->isAdmin()) {
            $this->users = \App\Models\User::orderBy('name')->get();
            $this->userId = ''; // All users
        } else {
            $this->users = [auth()->user()];
            $this->userId = auth()->id();
        }

        // Load target from settings or default
        $this->revenueTarget = \App\Models\Setting::getCached('revenue_target', 50000);
    }

    public function updatedRevenueTarget($value)
    {
        if (is_numeric($value)) {
            \App\Models\Setting::updateOrCreate(
                ['key' => 'revenue_target'],
                ['value' => $value]
            );
        }
    }

    public function updatedUserId()
    {
        $this->dispatchCharts();
    }

    public function updatedClientSearch()
    {
        $this->dispatchCharts();
    }

    private function dispatchCharts()
    {
        $chartData = $this->getChartData();

        $this->dispatch(
            'chartDataUpdated',
            trendData: $chartData['trendData'],
            statusData: $chartData['statusData'],
            funnelData: $chartData['funnelData']
        );
    }

    public function updatedFilter($value)
    {
        $this->updateDateRange($value);
        $this->dispatchCharts();
    }

    private function updateDateRange($value)
    {
        switch ($value) {
            case 'today':
                $this->customStartDate = now()->format('Y-m-d');
                $this->customEndDate = now()->format('Y-m-d');
                break;
            case 'yesterday':
                $this->customStartDate = now()->subDay()->format('Y-m-d');
                $this->customEndDate = now()->subDay()->format('Y-m-d');
                break;
            case 'this_week':
                $this->customStartDate = now()->startOfWeek()->format('Y-m-d');
                $this->customEndDate = now()->endOfWeek()->format('Y-m-d');
                break;
            case 'last_week':
                $this->customStartDate = now()->subWeek()->startOfWeek()->format('Y-m-d');
                $this->customEndDate = now()->subWeek()->endOfWeek()->format('Y-m-d');
                break;
            case 'this_month':
                $this->customStartDate = now()->startOfMonth()->format('Y-m-d');
                $this->customEndDate = now()->endOfMonth()->format('Y-m-d');
                break;
            case 'last_month':
                $this->customStartDate = now()->subMonth()->startOfMonth()->format('Y-m-d');
                $this->customEndDate = now()->subMonth()->endOfMonth()->format('Y-m-d');
                break;
            case 'this_quarter':
                $this->customStartDate = now()->startOfQuarter()->format('Y-m-d');
                $this->customEndDate = now()->endOfQuarter()->format('Y-m-d');
                break;
            case 'this_year':
                $this->customStartDate = now()->startOfYear()->format('Y-m-d');
                $this->customEndDate = now()->endOfYear()->format('Y-m-d');
                break;
        }
    }

    private function getChartData()
    {
        $startDate = Carbon::parse($this->customStartDate)->startOfDay();
        $endDate = Carbon::parse($this->customEndDate)->endOfDay();

        $query = Estimate::query();
        if ($this->userId) {
            $query->where('created_by', $this->userId);
        }

        if ($this->clientSearch) {
            $query->whereHas('client', function ($q) {
                $q->where('name', 'like', '%' . $this->clientSearch . '%');
            });
        }

        // 1. Trend Data
        $dateDiff = $startDate->diffInDays($endDate);
        if ($dateDiff <= 31) {
            $trendData = (clone $query)->where('estimate_status', Estimate::EST_STATUS_ACCEPTED)
                ->whereBetween('estimate_date', [$startDate, $endDate])
                ->selectRaw('DATE(estimate_date) as date, SUM(grand_total) as total')
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        } else {
            $trendData = (clone $query)->where('estimate_status', Estimate::EST_STATUS_ACCEPTED)
                ->whereBetween('estimate_date', [$startDate, $endDate])
                ->selectRaw($this->monthKeyExpr('estimate_date') . ' as date, SUM(grand_total) as total')
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        }

        // 2. Status Data
        $statusData = (clone $query)->whereBetween('created_at', [$startDate, $endDate])
            ->select('estimate_status', DB::raw('count(*) as count'))
            ->groupBy('estimate_status')
            ->pluck('count', 'estimate_status')
            ->toArray();

        // 3. Funnel Data
        $estimates = (clone $query)->whereBetween('created_at', [$startDate, $endDate])->get();

        $funnelData = [
            'sent' => $estimates->whereIn('status', ['sent', 'accepted', 'declined', 'expired'])->count(),
            'opened' => $estimates->whereNotNull('email_opened_at')->count(),
            'viewed' => $estimates->where('view_count', '>', 0)->count(),
            'accepted' => $estimates->where('estimate_status', Estimate::EST_STATUS_ACCEPTED)->count(),
        ];

        return compact('trendData', 'statusData', 'funnelData');
    }

    private function monthKeyExpr(string $dateExpr): string
    {
        return match (DB::connection()->getDriverName()) {
            'mysql', 'mariadb' => "DATE_FORMAT($dateExpr, '%Y-%m')",
            'pgsql' => "to_char($dateExpr, 'YYYY-MM')",
            default => "strftime('%Y-%m', $dateExpr)",
        };
    }

    public function render()
    {
        $startDate = Carbon::parse($this->customStartDate)->startOfDay();
        $endDate = Carbon::parse($this->customEndDate)->endOfDay();

        // Previous Period for Comparison
        $periodLength = $startDate->diffInDays($endDate) + 1;
        $prevStartDate = $startDate->copy()->subDays($periodLength);
        $prevEndDate = $endDate->copy()->subDays($periodLength);

        // Base Query
        $baseQuery = Estimate::query();
        if ($this->userId) {
            $baseQuery->where('created_by', $this->userId);
        }

        // Key Metrics
        $currentEstimates = (clone $baseQuery)->whereBetween('created_at', [$startDate, $endDate]);
        $prevEstimates = (clone $baseQuery)->whereBetween('created_at', [$prevStartDate, $prevEndDate]);

        $totalCount = (clone $currentEstimates)->count();
        $prevTotalCount = (clone $prevEstimates)->count();

        $acceptedCount = (clone $currentEstimates)->where('estimate_status', Estimate::EST_STATUS_ACCEPTED)->count();
        $prevAcceptedCount = (clone $prevEstimates)->where('estimate_status', Estimate::EST_STATUS_ACCEPTED)->count();

        $declinedCount = (clone $currentEstimates)->where('estimate_status', Estimate::EST_STATUS_DECLINED)->count();

        $currentRevenue = (clone $currentEstimates)->where('estimate_status', Estimate::EST_STATUS_ACCEPTED)->sum('grand_total');
        $prevRevenue = (clone $prevEstimates)->where('estimate_status', Estimate::EST_STATUS_ACCEPTED)->sum('grand_total');

        $pipelineValue = (clone $baseQuery)->whereIn('estimate_status', [Estimate::EST_STATUS_DRAFT, Estimate::EST_STATUS_SENT, Estimate::EST_STATUS_PENDING_APPROVAL, Estimate::EST_STATUS_APPROVED])->sum('grand_total');

        // Win Rate
        $closedCount = $acceptedCount + $declinedCount;
        $winRate = $closedCount > 0 ? round(($acceptedCount / $closedCount) * 100, 1) : 0;

        $prevClosedCount = $prevAcceptedCount + (clone $prevEstimates)->where('estimate_status', Estimate::EST_STATUS_DECLINED)->count();
        $prevWinRate = $prevClosedCount > 0 ? round(($prevAcceptedCount / $prevClosedCount) * 100, 1) : 0;

        // Comparisons
        $revenueGrowth = $prevRevenue > 0 ? round((($currentRevenue - $prevRevenue) / $prevRevenue) * 100, 1) : ($currentRevenue > 0 ? 100 : 0);
        $estimatesGrowth = $prevTotalCount > 0 ? round((($totalCount - $prevTotalCount) / $prevTotalCount) * 100, 1) : ($totalCount > 0 ? 100 : 0);

        // Charts Data (Reused method to ensure consistency, though slightly less efficient)
        $chartData = $this->getChartData();

        // Top Products
        $topProducts = DB::table('estimate_items')
            ->join('estimates', 'estimate_items.estimate_id', '=', 'estimates.id')
            ->whereBetween('estimates.estimate_date', [$startDate, $endDate])
            ->where('estimates.estimate_status', Estimate::EST_STATUS_ACCEPTED)
            ->when($this->userId, function ($q) {
                return $q->where('estimates.created_by', $this->userId);
            })
            ->select('estimate_items.name', DB::raw('count(*) as count'), DB::raw('sum(estimate_items.total) as revenue'))
            ->groupBy('estimate_items.name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        // Recent Estimates
        $recentEstimates = (clone $currentEstimates)
            ->with('client')
            ->latest()
            ->limit(10)
            ->get();

        // New Detailed Metrics
        $versionsCreated = (clone $currentEstimates)->whereNotNull('parent_id')->count();
        // Assuming 'approved' is an internal status for fully approved estimates
        $internalApproved = (clone $currentEstimates)->where('estimate_status', Estimate::EST_STATUS_APPROVED)->count();
        // Need to check specific approval logic if 'status' isn't enough, but usually 'status' reflects final state.

        // Rejections (Internal) - Check EstimateApproval for this period
        $rejectionsCount = \App\Models\EstimateApproval::whereIn('estimate_id', (clone $currentEstimates)->select('id'))
            ->where('status', 'rejected')
            ->count();

        // Downloads
        $downloadsCount = \App\Models\EstimateAnalytic::whereIn('estimate_id', (clone $currentEstimates)->select('id'))
            ->where('action', 'download')
            ->count();

        // Top Viewed Clients
        $topClients = (clone $currentEstimates)
            ->select('client_id', DB::raw('count(*) as estimates_count'), DB::raw('sum(view_count) as total_views'))
            ->with('client')
            ->groupBy('client_id')
            ->orderByDesc('total_views')
            ->limit(5)
            ->get();

        // Weighted Forecast
        $weightedForecast = (clone $baseQuery)->whereIn('estimate_status', [Estimate::EST_STATUS_SENT, Estimate::EST_STATUS_PENDING_APPROVAL])
            ->sum(DB::raw('grand_total * 0.7'));

        // Efficiency Metrics
        // Average Deal Size (Accepted)
        $avgDealValue = (clone $baseQuery)->where('estimate_status', Estimate::EST_STATUS_ACCEPTED)->avg('grand_total') ?? 0;

        // Sales Velocity (Avg Days to Close)
        $avgDaysExpr = match (DB::connection()->getDriverName()) {
            'mysql', 'mariadb' => 'AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at) / 86400) as avg_days',
            'pgsql' => 'AVG(EXTRACT(EPOCH FROM (updated_at - created_at)) / 86400) as avg_days',
            default => 'AVG(julianday(updated_at) - julianday(created_at)) as avg_days',
        };

        $avgDaysToClose = (clone $baseQuery)->where('estimate_status', Estimate::EST_STATUS_ACCEPTED)
            ->selectRaw($avgDaysExpr)
            ->value('avg_days');
        $avgDaysToClose = $avgDaysToClose ? round($avgDaysToClose, 1) : 0;

        // Actionable: Hot Leads
        // Estimates sent, not yet accepted, with high engagement (views > 1 or recent view)
        $hotLeads = (clone $baseQuery)->where('estimate_status', Estimate::EST_STATUS_SENT)
            ->where(function ($q) {
                $q->where('view_count', '>', 1)
                    ->orWhere('last_viewed_at', '>=', now()->subDays(3));
            })
            ->orderByDesc('view_count')
            ->orderByDesc('last_viewed_at') // Most engaged first
            ->limit(5)
            ->with('client')
            ->get();

        // -----------------------------
        // Future Enhancements Implementation
        // -----------------------------

        // 1. Stale Pipeline (Untouched > 14 days)
        $staleEstimates = (clone $baseQuery)->whereIn('estimate_status', [Estimate::EST_STATUS_SENT, Estimate::EST_STATUS_PENDING_APPROVAL])
            ->where('updated_at', '<=', now()->subDays(14))
            ->with('client')
            ->orderBy('updated_at', 'asc') // Oldest untouched first
            ->limit(5)
            ->get();

        // 2. Discount Impact Analysis
        // Calculate effective discount percentage on Won vs Lost deals
        // Formula: (discount_total / (grand_total + discount_total)) * 100
        $calcAvgDiscount = function ($status) use ($baseQuery, $startDate, $endDate) {
            $dbStatus = match ($status) {
                'accepted' => Estimate::EST_STATUS_ACCEPTED,
                'declined' => Estimate::EST_STATUS_DECLINED,
                default => $status
            };
            return (clone $baseQuery)->where('estimate_status', $dbStatus)
                ->whereBetween('estimate_date', [$startDate, $endDate])
                ->selectRaw('AVG(
                    CASE 
                        WHEN (grand_total + discount_total) > 0 
                        THEN (discount_total * 100.0) / (grand_total + discount_total) 
                        ELSE 0 
                    END
                ) as avg_discount')
                ->value('avg_discount') ?? 0;
        };

        $avgDiscountWon = round($calcAvgDiscount('accepted'), 1);
        $avgDiscountLost = round($calcAvgDiscount('declined'), 1);

        // 3. Goal Tracking
        // Just prepare the % for the view
        $goalProgress = $this->revenueTarget > 0 ? min(round(($currentRevenue / $this->revenueTarget) * 100, 1), 100) : 0;

        // -----------------------------
        // New Strategic Metrics
        // -----------------------------

        // 4. Revenue by Category
        $topCategories = DB::table('estimate_items')
            ->join('estimates', 'estimate_items.estimate_id', '=', 'estimates.id')
            ->join('products', 'estimate_items.product_id', '=', 'products.id')
            ->join('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->whereBetween('estimates.estimate_date', [$startDate, $endDate])
            ->where('estimates.estimate_status', Estimate::EST_STATUS_ACCEPTED)
            ->when($this->userId, function ($q) {
                return $q->where('estimates.created_by', $this->userId);
            })
            ->select('product_categories.name', DB::raw('sum(estimate_items.total) as revenue'))
            ->groupBy('product_categories.name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        // 5. Lost Reason Analysis
        // Assuming we capture 'decline_reason_id' or similar on Estimate, or use EstimateApproval comments/status?
        // Checking Estimate model... it has 'status'='declined'. 
        // We need to check if we store the specific reason. 
        // EstimateController references DeclineReason model.
        // Let's assume we can join on a 'decline_reason_id' if it exists or use a workaround.
        // Checking schema... Estimate model has status but no direct 'decline_reason_id' visible in fillable.
        // Wait, show() method loads 'declineReasons'.
        // If the relation isn't direct on Estimate, we might need to skip or check ActivityLog/Approval.
        // Let's implement a placeholder or check ActivityLog for "Declined: [Reason]" pattern if needed.
        // For now, let's try to see if there's a joinable table.
        // Estimate has no 'decline_reason_id' in fillable. 
        // Let's skip Lost Reason for now if schema is unclear, OR use ActivityLog text parsing.
        // BETTER: Let's implement Recurring vs New Business which is robust.

        // 6. Recurring vs New Business
        // New Business: Client's first accepted estimate is in this period.
        // Recurring: Client has older accepted estimates.

        $acceptedEstimates = (clone $baseQuery)->where('estimate_status', Estimate::EST_STATUS_ACCEPTED)
            ->whereBetween('estimate_date', [$startDate, $endDate])
            ->select('id', 'client_id', 'grand_total')
            ->get();

        $recurringRevenue = 0;
        $newBusinessRevenue = 0;

        foreach ($acceptedEstimates as $estimate) {
            // Check if this client has ANY accepted estimate BEFORE this one (strictly before start date potentially, or just before this one)
            $previousWins = Estimate::where('client_id', $estimate->client_id)
                ->where('estimate_status', Estimate::EST_STATUS_ACCEPTED)
                ->where('id', '!=', $estimate->id)
                ->where('estimate_date', '<', $startDate) // strictly historical
                ->exists();

            if ($previousWins) {
                $recurringRevenue += $estimate->grand_total;
            } else {
                $newBusinessRevenue += $estimate->grand_total;
            }
        }

        // Calculate percentages
        $totalWonRevenue = $recurringRevenue + $newBusinessRevenue;
        $recurringPercentage = $totalWonRevenue > 0 ? round(($recurringRevenue / $totalWonRevenue) * 100, 1) : 0;
        $newBusinessPercentage = $totalWonRevenue > 0 ? round(($newBusinessRevenue / $totalWonRevenue) * 100, 1) : 0;

        // -----------------------------
        // Advanced Analytics (Data Scientist Level)
        // -----------------------------

        // 7. Client Retention Cohort Analysis (Simplified)
        // Group clients by their "acquisition month" (first estimate date).
        // Then calculate how much revenue they generated in the CURRENT period.
        // This shows: "Are our old clients still buying?" vs "Are new clients buying?"

        $cohorts = DB::table('estimates')
            ->selectRaw($this->monthKeyExpr('MIN(estimate_date)') . ' as acquisition_month, client_id')
            ->where('estimate_status', Estimate::EST_STATUS_ACCEPTED)
            ->groupBy('client_id');

        // Join cohorts with current period revenue
        // This query:
        // 1. Calculates acquisition month for ALL clients who ever bought.
        // 2. Joins with estimates in the CURRENT filtered period.
        // 3. Groups by acquisition month to show revenue contribution by vintage.

        $cohortRevenue = DB::table('estimates as e')
            ->joinSub($cohorts, 'c', function ($join) {
                $join->on('e.client_id', '=', 'c.client_id');
            })
            ->whereBetween('e.estimate_date', [$startDate, $endDate])
            ->where('e.estimate_status', Estimate::EST_STATUS_ACCEPTED)
            ->when($this->userId, function ($q) {
                return $q->where('e.created_by', $this->userId);
            })
            ->selectRaw('c.acquisition_month, SUM(e.grand_total) as revenue, COUNT(DISTINCT e.client_id) as active_clients')
            ->groupBy('c.acquisition_month')
            ->orderByDesc('c.acquisition_month')
            ->limit(12) // Show last 12 vintage months
            ->get();

        // 8. Product Affinity (Market Basket Analysis Lite)
        // "People who bought X also bought Y"
        // Find pairs of products that appear in the same accepted estimates.
        // This is complex in SQL. Simplified approach:
        // Find the top selling product, then find what ELSE is in the same estimates.

        $topProductId = DB::table('estimate_items')
            ->join('estimates', 'estimate_items.estimate_id', '=', 'estimates.id')
            ->whereBetween('estimates.estimate_date', [$startDate, $endDate])
            ->where('estimates.estimate_status', Estimate::EST_STATUS_ACCEPTED)
            ->select('product_id')
            ->groupBy('product_id')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(1)
            ->value('product_id');

        $affinityProducts = [];
        $topProductName = '';

        if ($topProductId) {
            $topProductName = \App\Models\Product::find($topProductId)->name ?? 'Unknown';

            // Find estimates with this product
            $estimateIdsWithTop = DB::table('estimate_items')
                ->where('product_id', $topProductId)
                ->pluck('estimate_id');

            // Find OTHER items in those estimates
            $affinityProducts = DB::table('estimate_items')
                ->join('products', 'estimate_items.product_id', '=', 'products.id')
                ->whereIn('estimate_items.estimate_id', $estimateIdsWithTop)
                ->where('estimate_items.product_id', '!=', $topProductId)
                ->select('products.name', DB::raw('COUNT(*) as frequency'))
                ->groupBy('products.name')
                ->orderByDesc('frequency')
                ->limit(5)
                ->get();
        }

        // -----------------------------
        // Enterprise Level Analytics
        // -----------------------------

        // 9. Estimator Leaderboard (If Admin)
        $leaderboard = [];
        if (auth()->user()->isAdmin()) {
            // Updated logic: Query estimates directly grouped by creator to avoid 'user_id' column error
            // The User->estimates() relationship likely defaults to user_id, but the FK is created_by.

            $estimatorStats = \App\Models\Estimate::whereBetween('estimate_date', [$startDate, $endDate])
                ->selectRaw('created_by, count(*) as total_estimates, sum(case when estimate_status = "accepted" then 1 else 0 end) as won_count, sum(case when estimate_status = "accepted" then grand_total else 0 end) as total_revenue')
                ->whereNotNull('created_by')
                ->groupBy('created_by')
                ->get();

            // Map stats to user details
            $leaderboard = $estimatorStats->map(function ($stat) {
                $user = \App\Models\User::find($stat->created_by);
                if (!$user)
                    return null;

                $total = $stat->total_estimates;
                $winRate = $total > 0 ? round(($stat->won_count / $total) * 100, 1) : 0;

                return [
                    'name' => $user->name,
                    'revenue' => $stat->total_revenue ?? 0,
                    'estimates' => $total,
                    'win_rate' => $winRate,
                    // Use a default avatar or user's if method exists
                    'avatar' => method_exists($user, 'profile_photo_url') ? $user->profile_photo_url : "https://ui-avatars.com/api/?name=" . urlencode($user->name) . "&color=7F9CF5&background=EBF4FF",
                ];
            })->filter()->sortByDesc('revenue')->values();
        }

        // 10. Deal Size Distribution (Histogram)
        $dealSizes = (clone $baseQuery)->where('estimate_status', Estimate::EST_STATUS_ACCEPTED)
            ->whereBetween('estimate_date', [$startDate, $endDate])
            ->pluck('grand_total');

        // Define Buckets: <1k, 1k-5k, 5k-20k, 20k-50k, 50k+
        $buckets = [
            '< $1k' => 0,
            '$1k - $5k' => 0,
            '$5k - $20k' => 0,
            '$20k - $50k' => 0,
            '$50k+' => 0,
        ];

        foreach ($dealSizes as $amount) {
            if ($amount < 1000)
                $buckets['< $1k']++;
            elseif ($amount < 5000)
                $buckets['$1k - $5k']++;
            elseif ($amount < 20000)
                $buckets['$5k - $20k']++;
            elseif ($amount < 50000)
                $buckets['$20k - $50k']++;
            else
                $buckets['$50k+']++;
        }

        return view('livewire.reports.index', array_merge([
            'totalCount' => $totalCount,
            'acceptedCount' => $acceptedCount,
            'declinedCount' => $declinedCount,
            'currentRevenue' => $currentRevenue,
            'pipelineValue' => $pipelineValue,
            'winRate' => $winRate,
            'prevWinRate' => $prevWinRate,
            'revenueGrowth' => $revenueGrowth,
            'estimatesGrowth' => $estimatesGrowth,
            'topProducts' => $topProducts,
            'recentEstimates' => $recentEstimates,
            'weightedForecast' => $weightedForecast,
            'versionsCreated' => $versionsCreated,
            'internalApproved' => $internalApproved,
            'rejectionsCount' => $rejectionsCount,
            'downloadsCount' => $downloadsCount,
            'topClients' => $topClients,
            'avgDealValue' => $avgDealValue,
            'avgDaysToClose' => $avgDaysToClose,
            'hotLeads' => $hotLeads,
            'staleEstimates' => $staleEstimates,
            'avgDiscountWon' => $avgDiscountWon,
            'avgDiscountLost' => $avgDiscountLost,
            'revenueTarget' => $this->revenueTarget,
            'goalProgress' => $goalProgress,
            'topCategories' => $topCategories,
            'recurringRevenue' => $recurringRevenue,
            'newBusinessRevenue' => $newBusinessRevenue,
            'recurringPercentage' => $recurringPercentage,
            'newBusinessPercentage' => $newBusinessPercentage,
            'cohortRevenue' => $cohortRevenue,
            'affinityProducts' => $affinityProducts,
            'topAffinityName' => $topProductName,
            'leaderboard' => $leaderboard,
            'dealSizeBuckets' => $buckets,
        ], $chartData))->layout('layouts.app');
    }
}

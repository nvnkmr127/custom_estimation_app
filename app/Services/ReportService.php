<?php

namespace App\Services;

use App\Models\Estimate;
use App\Models\EstimateApproval;
use App\Models\EstimateAnalytic;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ReportService
{
    protected array $filters = [];
    protected Carbon $startDate;
    protected Carbon $endDate;

    public function setFilters(array $filters): self
    {
        $this->filters = $filters;
        $this->startDate = Carbon::parse($filters['startDate'] ?? now()->startOfMonth())->startOfDay();
        $this->endDate = Carbon::parse($filters['endDate'] ?? now()->endOfMonth())->endOfDay();
        return $this;
    }

    /**
     * Get Revenue Metrics
     * Revenue = Sum of grand_total for ACCEPTED estimates only
     */
    public function getRevenueMetrics(): array
    {
        $cacheKey = $this->getCacheKey('revenue_metrics');

        return Cache::remember($cacheKey, 300, function () {
            $current = $this->getBaseQuery()
                ->where('estimate_status', Estimate::EST_STATUS_ACCEPTED)
                ->whereBetween('accepted_at', [$this->startDate, $this->endDate])
                ->selectRaw('COUNT(*) as count, SUM(grand_total) as total')
                ->first();

            $prevPeriod = $this->getPreviousPeriodDates();
            $previous = $this->getBaseQuery()
                ->where('estimate_status', Estimate::EST_STATUS_ACCEPTED)
                ->whereBetween('accepted_at', [$prevPeriod['start'], $prevPeriod['end']])
                ->selectRaw('COUNT(*) as count, SUM(grand_total) as total')
                ->first();

            return [
                'count' => $current->count ?? 0,
                'total' => $current->total ?? 0,
                'growth' => $this->calculateGrowth($current->total ?? 0, $previous->total ?? 0),
                'prev_total' => $previous->total ?? 0,
            ];
        });
    }

    /**
     * Get Pipeline Metrics
     * Pipeline = Sum of grand_total for non-finalized estimates (Draft, Pending Approval, Approved, Sent)
     */
    public function getPipelineMetrics(): array
    {
        $cacheKey = $this->getCacheKey('pipeline_metrics');

        return Cache::remember($cacheKey, 300, function () {
            $pipelineStatuses = [
                Estimate::EST_STATUS_DRAFT,
                Estimate::EST_STATUS_PENDING_APPROVAL,
                Estimate::EST_STATUS_APPROVED,
                Estimate::EST_STATUS_SENT
            ];

            return $this->getBaseQuery()
                ->whereIn('estimate_status', $pipelineStatuses)
                ->selectRaw('COUNT(*) as count, SUM(grand_total) as total')
                ->first()
                ->toArray();
        });
    }

    /**
     * Get Conversion Rates
     * Accepted / Sent
     */
    public function getConversionRates(): array
    {
        $cacheKey = $this->getCacheKey('conversion_rates');

        return Cache::remember($cacheKey, 300, function () {
            $stats = $this->getBaseQuery()
                ->whereBetween('created_at', [$this->startDate, $this->endDate])
                ->selectRaw('
                    COUNT(CASE WHEN estimate_status = ? THEN 1 END) as accepted_count,
                    COUNT(CASE WHEN estimate_status IN (?, ?, ?, ?) THEN 1 END) as sent_count
                ', [
                    Estimate::EST_STATUS_ACCEPTED,
                    Estimate::EST_STATUS_SENT,
                    Estimate::EST_STATUS_ACCEPTED,
                    Estimate::EST_STATUS_DECLINED,
                    Estimate::EST_STATUS_EXPIRED
                ])
                ->first();

            $rate = $stats->sent_count > 0 ? ($stats->accepted_count / $stats->sent_count) * 100 : 0;

            return [
                'accepted' => $stats->accepted_count,
                'sent' => $stats->sent_count,
                'rate' => round($rate, 1)
            ];
        });
    }

    /**
     * Get Approval Metrics
     * Approved / Pending
     */
    public function getApprovalMetrics(): array
    {
        $cacheKey = $this->getCacheKey('approval_metrics');

        return Cache::remember($cacheKey, 300, function () {
            $stats = $this->getBaseQuery()
                ->whereBetween('created_at', [$this->startDate, $this->endDate])
                ->selectRaw('
                    COUNT(CASE WHEN estimate_status IN (?, ?, ?, ?, ?) THEN 1 END) as approved_count,
                    COUNT(CASE WHEN estimate_status = ? THEN 1 END) as pending_count
                ', [
                    Estimate::EST_STATUS_APPROVED,
                    Estimate::EST_STATUS_SENT,
                    Estimate::EST_STATUS_ACCEPTED,
                    Estimate::EST_STATUS_DECLINED,
                    Estimate::EST_STATUS_EXPIRED,
                    Estimate::EST_STATUS_PENDING_APPROVAL
                ])
                ->first();

            $rate = $stats->pending_count > 0 ? ($stats->approved_count / $stats->pending_count) * 100 : 0;

            return [
                'approved' => $stats->approved_count,
                'pending' => $stats->pending_count,
                'rate' => round($rate, 1)
            ];
        });
    }

    /**
     * Get Sales Funnel Data
     */
    public function getSalesFunnel(): array
    {
        $query = $this->getBaseQuery()
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);

        $fullFunnel = $query->selectRaw('
            COUNT(*) as total,
            COUNT(CASE WHEN estimate_status IN (?, ?, ?, ?) THEN 1 END) as sent,
            SUM(CASE WHEN view_count > 0 THEN 1 ELSE 0 END) as viewed,
            COUNT(CASE WHEN estimate_status = ? THEN 1 END) as accepted
        ', [
            Estimate::EST_STATUS_SENT,
            Estimate::EST_STATUS_ACCEPTED,
            Estimate::EST_STATUS_DECLINED,
            Estimate::EST_STATUS_EXPIRED,
            Estimate::EST_STATUS_ACCEPTED
        ])->first();

        return [
            ['stage' => 'Created', 'count' => $fullFunnel->total],
            ['stage' => 'Sent', 'count' => $fullFunnel->sent],
            ['stage' => 'Viewed', 'count' => (int)$fullFunnel->viewed],
            ['stage' => 'Accepted', 'count' => $fullFunnel->accepted],
        ];
    }

    /**
     * Get Revenue Trends
     */
    public function getRevenueTrends(): array
    {
        $dateDiff = $this->startDate->diffInDays($this->endDate);
        
        if ($dateDiff <= 31) {
            $groupBy = 'DATE(accepted_at)';
            $format = 'Y-m-d';
        } else {
            $groupBy = $this->monthKeyExpr('accepted_at');
            $format = 'Y-m';
        }

        return $this->getBaseQuery()
            ->where('estimate_status', Estimate::EST_STATUS_ACCEPTED)
            ->whereBetween('accepted_at', [$this->startDate, $this->endDate])
            ->selectRaw("$groupBy as date, SUM(grand_total) as total")
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * Get Sales Performance (Leaderboard)
     */
    public function getSalesPerformance(): array
    {
        return $this->getBaseQuery()
            ->whereBetween('estimates.created_at', [$this->startDate, $this->endDate])
            ->join('users', 'estimates.created_by', '=', 'users.id')
            ->selectRaw('
                users.id as user_id,
                users.name as user_name,
                COUNT(estimates.id) as total_estimates,
                SUM(CASE WHEN estimates.estimate_status = ? THEN estimates.grand_total ELSE 0 END) as revenue,
                COUNT(CASE WHEN estimates.estimate_status = ? THEN 1 END) as won_count
            ', [Estimate::EST_STATUS_ACCEPTED, Estimate::EST_STATUS_ACCEPTED])
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('revenue')
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->user_id,
                    'name' => $row->user_name,
                    'total_estimates' => $row->total_estimates,
                    'revenue' => (float)$row->revenue,
                    'won_count' => $row->won_count,
                    'win_rate' => $row->total_estimates > 0 ? round(($row->won_count / $row->total_estimates) * 100, 1) : 0
                ];
            })
            ->toArray();
    }

    /**
     * Get Discount Analysis
     */
    public function getDiscountAnalysis(): array
    {
        return $this->getBaseQuery()
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->selectRaw('
                estimate_status,
                AVG(CASE WHEN (grand_total + discount_total) > 0 THEN (discount_total * 100.0) / (grand_total + discount_total) ELSE 0 END) as avg_discount
            ')
            ->whereIn('estimate_status', [Estimate::EST_STATUS_ACCEPTED, Estimate::EST_STATUS_DECLINED])
            ->groupBy('estimate_status')
            ->get()
            ->pluck('avg_discount', 'estimate_status')
            ->toArray();
    }

    /**
     * Get Status Breakdown for Charts
     */
    public function getStatusBreakdown(): array
    {
        $cacheKey = $this->getCacheKey('status_breakdown');

        return Cache::remember($cacheKey, 300, function () {
            return $this->getBaseQuery()
                ->whereBetween('estimates.created_at', [$this->startDate, $this->endDate])
                ->selectRaw('estimate_status, COUNT(*) as count')
                ->groupBy('estimate_status')
                ->get()
                ->pluck('count', 'estimate_status')
                ->toArray();
        });
    }

    /**
     * Public access to the filtered base query for drill-downs and exports
     */
    public function getDrilldownQuery()
    {
        return $this->getBaseQuery();
    }

    /**
     * Base Query with common filters and "current version" strictness
     */
    protected function getBaseQuery()
    {
        $query = Estimate::query()->current();

        if (!empty($this->filters['userId'])) {
            $query->where('estimates.created_by', $this->filters['userId']);
        }

        if (!empty($this->filters['clientId'])) {
            $query->where('estimates.client_id', $this->filters['clientId']);
        }

        if (!empty($this->filters['status']) && $this->filters['status'] !== 'all') {
            $query->where('estimates.estimate_status', $this->filters['status']);
        }

        return $query;
    }

    protected function getPreviousPeriodDates(): array
    {
        $days = $this->startDate->diffInDays($this->endDate) + 1;
        return [
            'start' => $this->startDate->copy()->subDays($days),
            'end' => $this->endDate->copy()->subDays($days),
        ];
    }

    protected function calculateGrowth($current, $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        return round((($current - $previous) / $previous) * 100, 1);
    }

    protected function getCacheKey(string $metric): string
    {
        $filterHash = md5(json_encode($this->filters));
        return "reports_{$metric}_{$filterHash}";
    }

    protected function monthKeyExpr(string $field): string
    {
        return match (DB::connection()->getDriverName()) {
            'mysql', 'mariadb' => "DATE_FORMAT($field, '%Y-%m')",
            'pgsql' => "to_char($field, 'YYYY-MM')",
            default => "strftime('%Y-%m', $field)",
        };
    }
}

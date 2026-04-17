<?php

namespace App\Livewire\Reports;

use App\Models\Estimate;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $filter = 'this_month';
    public $customStartDate;
    public $customEndDate;
    public $userId = '';
    public $clientId = '';
    public $status = 'all';
    
    // UI State
    public $activeTab = 'summary'; // summary, revenue, sales, performance, funnel
    public $revenueTarget;
    public $isLoading = false;

    protected $queryString = [
        'filter' => ['except' => 'this_month'],
        'userId' => ['except' => ''],
        'clientId' => ['except' => ''],
        'status' => ['except' => 'all'],
    ];

    public function mount()
    {
        $this->updateDateRange($this->filter);
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

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['userId', 'clientId', 'status', 'customStartDate', 'customEndDate', 'filter'])) {
            if ($propertyName === 'filter') {
                $this->updateDateRange($this->filter);
            }
            $this->resetPage();
            $this->dispatch('chart-updated', [
                'revenueTrend' => $this->getReportService()->getRevenueTrends()
            ]);
        }
    }

    protected function getReportService(): ReportService
    {
        return app(ReportService::class)->setFilters([
            'startDate' => $this->customStartDate,
            'endDate' => $this->customEndDate,
            'userId' => $this->userId,
            'clientId' => $this->clientId,
            'status' => $this->status,
        ]);
    }

    public function exportCsv()
    {
        $service = $this->getReportService();
        $revenue = $service->getRevenueMetrics();
        $pipeline = $service->getPipelineMetrics();
        
        $filename = "report-" . now()->format('Y-m-d') . ".csv";
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($revenue, $pipeline) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Report Metric', 'Count', 'Value', 'Growth (%)']);
            fputcsv($file, ['Total Revenue (Accepted)', $revenue['count'], $revenue['total'], $revenue['growth']]);
            fputcsv($file, ['Pipeline Value', $pipeline['count'], $pipeline['total'], 'N/A']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        $service = $this->getReportService();
        $startDate = Carbon::parse($this->customStartDate)->startOfDay();
        $endDate = Carbon::parse($this->customEndDate)->endOfDay();

        // Get the base query from service and apply date range specifically for the drill-down list
        // Note: ReportService methods handle their own date ranges internally (e.g. accepted_at for revenue)
        // For the general list, we'll use created_at within the filtered range.
        $drilldownQuery = $service->getDrilldownQuery()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['client', 'creator'])
            ->latest();

        return view('livewire.reports.index', [
            'revenue' => $service->getRevenueMetrics(),
            'pipeline' => $service->getPipelineMetrics(),
            'conversion' => $service->getConversionRates(),
            'approval' => $service->getApprovalMetrics(),
            'funnel' => $service->getSalesFunnel(),
            'revenueTrend' => $service->getRevenueTrends(),
            'performance' => $service->getSalesPerformance(),
            'discountAnalysis' => $service->getDiscountAnalysis(),
            'users' => User::orderBy('name')->get(),
            'clients' => \App\Models\Client::orderBy('name')->get(),
            'recentEstimates' => $drilldownQuery->paginate(10),
            'revenueTarget' => $this->revenueTarget,
            'goalProgress' => $this->revenueTarget > 0 ? min(round(($service->getRevenueMetrics()['total'] / $this->revenueTarget) * 100, 1), 100) : 0,
        ])->layout('layouts.app');
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
}

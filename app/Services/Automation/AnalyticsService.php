<?php

namespace App\Services\Automation;

use App\Models\Automation;
use App\Models\AutomationExecutionLog;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsService
{
    /**
     * Aggregate stats for a specific date (defaults to yesterday).
     * This is intended to be run daily via a scheduled command.
     */
    public function aggregateDailyStats(?string $date = null): void
    {
        $targetDate = $date ? Carbon::parse($date) : Carbon::yesterday();
        $startOfDay = $targetDate->copy()->startOfDay();
        $endOfDay = $targetDate->copy()->endOfDay();

        // 1. Aggregate Automation Level Stats
        $automationStats = AutomationExecutionLog::whereBetween('started_at', [$startOfDay, $endOfDay])
            ->select(
                'automation_id',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as successful'),
                DB::raw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed'),
                DB::raw('SUM(duration_ms) as total_duration')
            )
            ->groupBy('automation_id')
            ->get();

        foreach ($automationStats as $stat) {
            DB::table('automation_daily_stats')->updateOrInsert(
                [
                    'automation_id' => $stat->automation_id,
                    'date' => $targetDate->format('Y-m-d')
                ],
                [
                    'total_executions' => $stat->total,
                    'successful_executions' => $stat->successful,
                    'failed_executions' => $stat->failed,
                    'total_duration_ms' => $stat->total_duration ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 2. Aggregate Step Level Stats (Approximate from JSON payload of logs if steps aren't logged individually in a table)
        // Since we don't have a separate `automation_step_logs` table (unless implemented earlier), we might skip this 
        // OR checks if execution logs have detailed step info.
        // Assuming for now we primarily track automation-level health.
        // If we want step stats, we'd need to parse the `execution_trace` JSON or query a step_logs table.
        // Let's postpone step-level deep dive to keep it simple for now, or check if we have step logs.
    }

    /**
     * Get dashboard overview stats (e.g., last 30 days).
     */
    public function getDashboardStats(int $days = 30): array
    {
        $startDate = Carbon::today()->subDays($days);

        // Fetch daily aggregated data
        $dailyData = DB::table('automation_daily_stats')
            ->where('date', '>=', $startDate->format('Y-m-d'))
            ->orderBy('date')
            ->get();

        // Formatting for Charts
        $labels = [];
        $totalExecutions = [];
        $successRate = [];

        // Group by date to sum across all automations
        $groupedByDate = $dailyData->groupBy('date');

        for ($i = $days; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->format('Y-m-d');
            $dayStats = $groupedByDate->get($date);

            $labels[] = Carbon::parse($date)->format('M d');

            if ($dayStats) {
                $total = $dayStats->sum('total_executions');
                $success = $dayStats->sum('successful_executions');
                $totalExecutions[] = $total;
                $successRate[] = $total > 0 ? round(($success / $total) * 100, 1) : 0;
            } else {
                $totalExecutions[] = 0;
                $successRate[] = 0;
            }
        }

        // Top Performing Automations (Volume)
        $topAutomations = DB::table('automation_daily_stats')
            ->join('automations', 'automation_daily_stats.automation_id', '=', 'automations.id')
            ->where('date', '>=', $startDate->format('Y-m-d'))
            ->select(
                'automations.name',
                DB::raw('SUM(total_executions) as total'),
                DB::raw('SUM(successful_executions) as success'),
                DB::raw('SUM(failed_executions) as failed')
            )
            ->groupBy('automations.id', 'automations.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return [
            'chart' => [
                'labels' => $labels,
                'executions' => $totalExecutions,
                'success_rate' => $successRate
            ],
            'top_automations' => $topAutomations,
            'summary' => [
                'total_runs' => array_sum($totalExecutions),
                'avg_success_rate' => count($successRate) > 0 ? round(array_sum($successRate) / count($successRate), 1) : 0
            ]
        ];
    }

    /**
     * Get detailed stats for a specific automation
     */
    public function getAutomationStats(int $automationId, int $days = 30): array
    {
        $startDate = Carbon::today()->subDays($days);

        $stats = DB::table('automation_daily_stats')
            ->where('automation_id', $automationId)
            ->where('date', '>=', $startDate->format('Y-m-d'))
            ->orderBy('date')
            ->get();

        return [
            'dates' => $stats->pluck('date'),
            'totals' => $stats->pluck('total_executions'),
            'success' => $stats->pluck('successful_executions'),
            'failed' => $stats->pluck('failed_executions'),
            'duration' => $stats->map(fn($s) => $s->total_executions > 0 ? round($s->total_duration_ms / $s->total_executions) : 0)
        ];
    }
}

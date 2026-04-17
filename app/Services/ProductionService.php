<?php

namespace App\Services;

use App\Models\Estimate;
use App\Models\Task;
use App\Models\Setting;

class ProductionService
{
    /**
     * Get the current production load percentage.
     */
    public function getCapacityLoad()
    {
        $maxCapacity = (int) Setting::get('daily_production_capacity', 10); // Default 10 units/tasks
        $activeTasks = Task::whereIn('status', ['pending', 'in_progress'])->count();

        if ($maxCapacity <= 0) return 0;

        return min(100, round(($activeTasks / $maxCapacity) * 100));
    }

    /**
     * Get large pending estimates (Potential Production Impact).
     */
    public function getPendingImpact()
    {
        // Define "Large" as top 20% of estimates or > some threshold
        $threshold = 500000; // 5 Lakhs for example

        return Estimate::current()
            ->whereIn('estimate_status', [Estimate::EST_STATUS_SENT, Estimate::EST_STATUS_APPROVED])
            ->where('grand_total', '>=', $threshold)
            ->get();
    }

    /**
     * Get recommended delivery delay based on current load.
     */
    public function getRecommendedDelay()
    {
        $load = $this->getCapacityLoad();

        if ($load > 90) return 14; // 2 weeks delay
        if ($load > 70) return 7;  // 1 week delay
        return 3;                 // Standard 3 days
    }
}

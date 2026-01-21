<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Automation\AnalyticsService;

class CalculateAutomationAnalytics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'automation:analytics-calculate {date? : The date to calculate stats for (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate daily analytics statistics for automation rules';

    /**
     * Execute the console command.
     */
    public function handle(AnalyticsService $service)
    {
        $date = $this->argument('date');
        $this->info("Calculating automation analytics for " . ($date ?? 'yesterday') . "...");

        try {
            $service->aggregateDailyStats($date);
            $this->info('Analytics calculation completed successfully.');
        } catch (\Exception $e) {
            $this->error('Failed to calculate analytics: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}

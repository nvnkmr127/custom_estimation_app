<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckEstimateExpiry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'estimates:check-expiry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiredCount = \App\Models\Estimate::whereIn('status', ['sent', 'waiting_approval'])
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now()->toDateString())
            ->update(['status' => 'expired']);

        if ($expiredCount > 0) {
            $this->info("Successfully marked {$expiredCount} estimates as expired.");

            // Log this action if needed, though mass update doesn't trigger model events.
            // For individual logging, we'd need to loop, but mass update is more efficient.
            \App\Models\ActivityLog::create([
                'action' => 'system_auto_expiry',
                'description' => "System automatically marked {$expiredCount} estimates as expired.",
                'properties' => ['count' => $expiredCount],
            ]);
        } else {
            $this->info("No estimates found to expire.");
        }
    }
}

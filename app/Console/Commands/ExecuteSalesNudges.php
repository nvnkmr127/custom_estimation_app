<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SalesNudgeService;

class ExecuteSalesNudges extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sales:execute-nudges';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically follow up on neglected or expiring estimates';

    /**
     * Execute the console command.
     */
    public function handle(SalesNudgeService $nudgeService)
    {
        $this->info('Starting Sales Nudge execution...');

        // 1. Handle Neglected Estimates (Follow-up)
        $neglected = $nudgeService->getNeglectedEstimates();
        $this->info("Found {$neglected->count()} neglected estimates.");
        
        foreach ($neglected as $estimate) {
            if ($nudgeService->nudge($estimate, 'neglect')) {
                $this->info("Nudge sent for #{$estimate->estimate_number}");
            }
        }

        // 2. Handle Expiring Estimates (Last Chance)
        $expiring = $nudgeService->getExpiringEstimates();
        $this->info("Found {$expiring->count()} expiring estimates.");

        foreach ($expiring as $estimate) {
            // For expiring, we might want a different logic like 'extend' or 'discount'
            // But for now, a standard follow-up is a powerful start.
            if ($nudgeService->nudge($estimate, 'expiring')) {
                $this->info("Expiry warning sent for #{$estimate->estimate_number}");
            }
        }

        $this->info('Nudge execution complete.');
    }
}

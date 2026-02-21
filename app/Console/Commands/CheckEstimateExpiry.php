<?php

namespace App\Console\Commands;

use App\Models\Estimate;
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
    protected $description = 'Checks for estimates that have passed their expiry date and marks them as expired.';

    /**
     * Execute the console command.
     */
    public function handle(
        \App\Services\Estimates\EstimateStateService $stateService,
        \App\Core\Events\EventDispatcherInterface $dispatcher
    ) {
        $now = now();

        // Query criteria:
        // 1. expires_at is past
        // 2. Only strictly 'sent' estimates (Accepted/Declined never expire)
        $expiredEstimates = Estimate::where('expires_at', '<', $now)
            ->where('estimate_status', Estimate::EST_STATUS_SENT)
            ->get();

        if ($expiredEstimates->isEmpty()) {
            $this->info('No estimates found to expire.');
            return;
        }

        $count = 0;
        foreach ($expiredEstimates as $estimate) {
            /** @var Estimate $estimate */
            try {
                \Illuminate\Support\Facades\DB::transaction(function () use ($estimate, $stateService, $dispatcher) {
                    // Perform state transition (Handles locking, logging, and database side effects)
                    // We transition client_status to expired, which auto-updates estimate_status as well via StateService
                    $stateService->transitionClientStatus($estimate, \App\Models\Estimate::CLT_STATUS_EXPIRED, true);

                    // Dispatch Domain Event
                    $dispatcher->dispatch(new \App\Core\Events\Estimates\EstimateExpired($estimate));
                });

                $this->line("Estimate #{$estimate->estimate_number} (ID: {$estimate->id}) has expired.");
                $count++;
            } catch (\Exception $e) {
                $this->error("Failed to expire estimate #{$estimate->id}: " . $e->getMessage());
                \Log::error("Auto-expiry failed for estimate #{$estimate->id}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        $this->info("Successfully processed {$count} expired estimates.");
    }
}

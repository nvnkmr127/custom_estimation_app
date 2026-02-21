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
        // 2. Not already expired or voided
        // 3. Not accepted or declined by the client (Accepted never expires)
        $expiredEstimates = Estimate::where('expires_at', '<', $now)
            ->where('estimate_status', '!=', Estimate::EST_STATUS_EXPIRED)
            ->where('estimate_status', '!=', Estimate::EST_STATUS_VOID)
            ->whereNotIn('client_status', [Estimate::CLT_STATUS_ACCEPTED, Estimate::CLT_STATUS_DECLINED])
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
                    $stateService->transitionEstimateStatus($estimate, \App\Models\Estimate::EST_STATUS_EXPIRED);

                    // Keep legacy status field in sync
                    $estimate->update(['status' => \App\Models\Estimate::STATUS_EXPIRED]);

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

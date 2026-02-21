<?php

namespace App\Listeners;

use App\Core\Events\Estimates\EstimateAccepted;
use App\Jobs\SyncEstimateToPerfex;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class PerfexSyncListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        if ($event instanceof EstimateAccepted) {
            $estimate = $event->estimate;

            if ($estimate) {
                Log::info("PerfexSyncListener: Triggering sync for accepted Estimate #{$estimate->id}");
                SyncEstimateToPerfex::dispatch($estimate);
            }
        }
    }
}

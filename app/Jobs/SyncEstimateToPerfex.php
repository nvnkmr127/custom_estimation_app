<?php

namespace App\Jobs;

use App\Models\Estimate;
use App\Services\PerfexApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncEstimateToPerfex implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $estimate;

    /**
     * Create a new job instance.
     */
    public function __construct(Estimate $estimate)
    {
        $this->estimate = $estimate;
    }

    /**
     * Execute the job.
     */
    public function handle(PerfexApiService $perfexApi): void
    {
        try {
            $perfexApi->syncEstimate($this->estimate);
            Log::info("Estimate #{$this->estimate->estimate_number} synced to Perfex via background job.");
        } catch (\Exception $e) {
            Log::error("Failed to sync estimate #{$this->estimate->estimate_number} to Perfex: " . $e->getMessage());
            // Fail the job so it can be retried
            throw $e;
        }
    }
}

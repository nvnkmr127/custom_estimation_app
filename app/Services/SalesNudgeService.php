<?php

namespace App\Services;

use App\Models\Estimate;
use App\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SalesNudgeService
{
    protected $estimateService;

    public function __construct(EstimateService $estimateService)
    {
        $this->estimateService = $estimateService;
    }

    /**
     * Get estimates that need a nudge (Sent but not viewed for 48h).
     */
    public function getNeglectedEstimates()
    {
        return Estimate::current()
            ->where('estimate_status', Estimate::EST_STATUS_SENT)
            ->where('sent_at', '<=', now()->subHours(48))
            ->where(function ($q) {
                $q->whereNull('last_viewed_at')
                  ->orWhere('last_viewed_at', '<=', now()->subHours(48));
            })
            ->where('nudge_task_created', false) // Use this as a flag to prevent double nudging
            ->get();
    }

    /**
     * Get estimates expiring in 24 hours.
     */
    public function getExpiringEstimates()
    {
        return Estimate::current()
            ->where('estimate_status', Estimate::EST_STATUS_SENT)
            ->whereBetween('expires_at', [now(), now()->addHours(24)])
            ->get();
    }

    /**
     * Execute a nudge for a specific estimate.
     */
    public function nudge(Estimate $estimate, $reason = 'follow_up')
    {
        try {
            // 1. Send personalized email (reuse sendToClient or a new nudge email)
            // For now, we'll use sendToClient which logs the interaction.
            $this->estimateService->sendToClient($estimate);

            // 2. Mark as nudged
            $estimate->update(['nudge_task_created' => true]);

            // 3. Log Activity
            ActivityLog::log('sales_nudge_sent', $estimate, "Automated {$reason} nudge sent to client.");

            return true;
        } catch (\Exception $e) {
            Log::error("Sales Nudge Failed for Estimate #{$estimate->id}: " . $e->getMessage());
            return false;
        }
    }
}

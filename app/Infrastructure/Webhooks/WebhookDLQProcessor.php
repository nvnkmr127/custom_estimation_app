<?php

namespace App\Infrastructure\Webhooks;

use App\Models\WebhookDeadLetter;
use App\Models\WebhookDelivery;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class WebhookDLQProcessor
{
    public function __construct(
        protected WebhookReplayService $replayService
    ) {
    }

    /**
     * Requeue a dead letter (attempt to redeliver).
     * 
     * @param WebhookDeadLetter $deadLetter
     * @return bool Success
     */
    public function requeue(WebhookDeadLetter $deadLetter): bool
    {
        try {
            $delivery = $deadLetter->originalDelivery;

            if (!$delivery) {
                // If original delivery is gone, maybe create fresh from event?
                // For now, fail if no delivery record.
                Log::error("DLQ Requeue Failed: Original delivery not found for DLQ {$deadLetter->id}");
                return false;
            }

            // Use ReplayService to trigger delivery retry
            $this->replayService->replayDelivery($delivery);

            // Upon successful Requeue (dispatch), delete the DLQ entry?
            // "Manual requeue" usually means "Give it another shot".
            // If that fails, it will likely end up in DLQ again (new ID).
            // So removing this DLQ entry prevents duplicates.
            $deadLetter->delete();

            Log::info("DLQ Requeued: {$deadLetter->id} -> new delivery.");

            return true;
        } catch (\Exception $e) {
            Log::error("DLQ Requeue Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Purge old dead letters.
     * 
     * @param int $retentionDays
     * @return int Count of deleted
     */
    public function purge(int $retentionDays = 30): int
    {
        return WebhookDeadLetter::where('created_at', '<', Carbon::now()->subDays($retentionDays))
            ->delete();
    }
}

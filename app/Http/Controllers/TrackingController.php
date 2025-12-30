<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Estimate;

class TrackingController extends Controller
{
    /**
     * Tracking pixel for email opens
     */
    public function pixel($estimate_id)
    {
        $estimate = Estimate::find($estimate_id);

        if ($estimate) {
            $estimate->increment('view_count');
            $estimate->update([
                'email_opened_at' => now(), // Keep existing logic or strictly map to 'pixel' meaning 'viewed'? Pixel usually means viewed.
                'last_viewed_at' => now(),
            ]);

            $viewCount = $estimate->view_count;
            $threshold = config('estimation.nudge_threshold', 3);
            ActivityLog::log('email_opened', $estimate, "Estimate #{$estimate->estimate_number} viewed (Count: {$viewCount}) from IP: ".request()->ip());

            // Smart Nudge Logic: Threshold views, not accepted/declined/expired, no task yet
            if (
                $viewCount >= $threshold
                && ! in_array($estimate->status, ['accepted', 'declined', 'expired'])
                && ! $estimate->nudge_task_created
            ) {

                // Linking to Perfex
                $perfexService = new \App\Services\PerfexApiService;
                $relType = 'proposal';
                $relId = $estimate->perfex_proposal_id;

                if ($relId) {
                    $description = "Smart Nudge: Client has viewed Estimate #{$estimate->estimate_number} {$viewCount} times but not accepted.";

                    $taskData = [
                        'name' => 'Follow up on Estimate #'.$estimate->estimate_number,
                        'description' => $description,
                        'priority' => 3, // Medium/High
                        'startdate' => now()->format('Y-m-d'),
                        'rel_type' => $relType,
                        'rel_id' => $relId,
                    ];

                    $response = $perfexService->createTask($taskData);

                    if (isset($response['id']) || (isset($response['status']) && $response['status'] == true)) {
                        $estimate->update(['nudge_task_created' => true]);
                        ActivityLog::log('system_action', $estimate, 'Smart Nudge: CRM Task Created for follow-up.');
                    } else {
                        \Illuminate\Support\Facades\Log::warning('Smart Nudge Task Failed: '.json_encode($response));
                    }
                }
            }
        }

        // Return a 1x1 transparent pixel
        $pixel = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

        return response($pixel)
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}

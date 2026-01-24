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
            // Only tracking email open time, NOT incrementing view count (view count is for portal visits)
            $estimate->touch(); // Updated_at
            $estimate->update([
                'email_opened_at' => now(),
            ]);

            ActivityLog::log('email_opened', $estimate, "Estimate #{$estimate->estimate_number} email opened from IP: " . request()->ip());

            // NOTE: Removed Smart Nudge logic from Pixel. 
            // Nudges should only trigger on actual Portal Page Views to avoid false positives.
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

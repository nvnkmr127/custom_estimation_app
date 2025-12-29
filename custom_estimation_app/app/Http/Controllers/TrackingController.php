<?php

namespace App\Http\Controllers;

use App\Models\Estimate;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    /**
     * Tracking pixel for email opens
     */
    public function pixel($estimate_id)
    {
        $estimate = Estimate::find($estimate_id);

        if ($estimate) {
            $estimate->update([
                'email_opened_at' => now()
            ]);

            ActivityLog::log('email_opened', $estimate, "Email for Estimate #{$estimate->estimate_number} was opened.");
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

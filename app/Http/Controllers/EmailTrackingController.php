<?php

namespace App\Http\Controllers;

use App\Models\EmailLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmailTrackingController extends Controller
{
    /**
     * Track email open (1x1 pixel).
     */
    public function trackOpen($id)
    {
        try {
            $log = EmailLog::find($id);
            if ($log && is_null($log->opened_at)) {
                $log->update(['opened_at' => now()]);
            }
        } catch (\Exception $e) {
            // Fail silently to not break the image load
            Log::error('Email Open Tracking Error: ' . $e->getMessage());
        }

        // Return 1x1 transparent GIF
        $pixel = base64_decode('R0lGODlhAQABAJAAAP8AAAAAACH5BAUQAAAALAAAAAABAAEAAAICBAEAOw==');

        return response($pixel, 200)
            ->header('Content-Type', 'image/gif')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    /**
     * Track link click and redirect.
     */
    public function trackClick(Request $request, $id)
    {
        $targetUrl = $request->query('target');

        if (!$targetUrl) {
            abort(404);
        }

        try {
            $log = EmailLog::find($id);
            if ($log) {
                // If this is the first interaction, mark as open too
                if (is_null($log->opened_at)) {
                    $log->opened_at = now();
                }

                // Log the click
                $clicks = $log->click_data ?? [];
                $clicks[] = [
                    'url' => $targetUrl,
                    'clicked_at' => now()->toIso8601String(),
                    'ip' => $request->ip()
                ];

                $log->click_data = $clicks;
                $log->save();
            }
        } catch (\Exception $e) {
            Log::error('Email Click Tracking Error: ' . $e->getMessage());
        }

        return redirect()->away($targetUrl);
    }
}

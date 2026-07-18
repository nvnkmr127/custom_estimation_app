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

        // Open-redirect guard: same-origin targets are always safe; off-site targets
        // must carry the signature we attach when generating the tracked link. This
        // stops attacker-forged /tracking/click/x?target=https://evil.com phishing links.
        if (!$this->isSafeRedirect($request, $targetUrl)) {
            abort(403, 'Invalid redirect target.');
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

    /**
     * A redirect target is safe if it stays on our own host (relative or same-origin URL),
     * or if the tracked link carries a valid signature (attached at generation time).
     */
    private function isSafeRedirect(Request $request, string $targetUrl): bool
    {
        $targetHost = parse_url($targetUrl, PHP_URL_HOST);

        // Relative URL, or absolute URL pointing at our own host → same-origin, safe.
        if ($targetHost === null) {
            return true;
        }
        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        if ($appHost && strcasecmp($targetHost, $appHost) === 0) {
            return true;
        }

        // Off-site: only follow it if we signed this link.
        return $request->hasValidSignature();
    }
}

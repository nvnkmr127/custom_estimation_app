<?php

namespace App\Http\Controllers;

use App\Models\ShortUrl;
use App\Services\UrlShortenerService;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class ShortUrlController extends Controller
{
    public function __construct(
        protected UrlShortenerService $urlShortener
    ) {
    }

    /**
     * Redirect to the original URL
     *
     * @param string $code
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirect(Request $request, string $code)
    {
        // 1. Resolve basic long URL
        $longUrl = $this->urlShortener->resolve($code);

        if (!$longUrl) {
            $record = ShortUrl::where('short_code', $code)->first();
            if (!$record) {
                $record = ShortUrl::whereRaw('LOWER(short_code) = LOWER(?)', [$code])->first();
            }

            if ($record && $record->isExpired()) {
                abort(410, 'This link expired on ' . $record->expires_at->toDayDateTimeString());
            }

            abort(404, 'URL not found or expired');
        }

        // 2. Specialized View Handling (PDF/Download)
        // If user appends ?view=pdf or ?download=1 to the short URL, we try to route them to the PDF version
        if ($request->has('pdf') || $request->has('download') || ($request->get('view') === 'pdf')) {
            // Check if it's a portal URL
            if (preg_match('/\/portal\/estimates\/(\d+)/', $longUrl, $matches)) {
                $estimateId = $matches[1];
                $estimate = \App\Models\Estimate::find($estimateId);
                
                if ($estimate) {
                    // Generate a new signed download URL using the same expiration logic as public_url
                    $expiration = $estimate->expires_at ? $estimate->expires_at->endOfDay() : now()->addDays(30);
                    $pdfUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute('portal.download', $expiration, ['estimate' => $estimate->id]);
                    
                    return redirect()->away($pdfUrl);
                }
            }
        }

        return redirect()->away($longUrl);
    }
}

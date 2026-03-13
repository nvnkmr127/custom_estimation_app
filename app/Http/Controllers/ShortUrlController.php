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
    public function redirect(string $code)
    {
        $longUrl = $this->urlShortener->resolve($code);

        if (!$longUrl) {
            $record = ShortUrl::where('short_code', $code)->first();
            if (!$record) {
                // Try case-insensitive fallback for reporting
                $record = ShortUrl::whereRaw('LOWER(short_code) = LOWER(?)', [$code])->first();
            }

            if ($record && $record->isExpired()) {
                return response()->json([
                    'error' => 'URL Expired',
                    'message' => 'This link expired on ' . $record->expires_at->toDayDateTimeString(),
                    'code' => $code
                ], 410);
            }

            abort(404, 'URL not found or expired');
        }

        return redirect()->away($longUrl);
    }
}

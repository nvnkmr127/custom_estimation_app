<?php

namespace App\Services;

use App\Models\ShortUrl;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UrlShortenerService
{
    /**
     * Shorten a URL and store it in the database
     *
     * @param string $longUrl The original long URL
     * @param int|null $expiresInDays Number of days until the short URL expires (null for no expiration)
     * @return string The shortened URL
     */
    public function shorten(string $longUrl, ?int $expiresInDays = 30): string
    {
        // Check if this URL has already been shortened recently
        $existing = ShortUrl::where('long_url', $longUrl)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($existing) {
            return $this->buildShortUrl($existing->short_code);
        }

        // Generate a unique short code
        $shortCode = $this->generateUniqueCode();

        // Calculate expiration
        $expiresAt = $expiresInDays ? now()->addDays($expiresInDays) : null;

        // Store the short URL
        ShortUrl::create([
            'short_code' => $shortCode,
            'long_url' => $longUrl,
            'expires_at' => $expiresAt,
            'clicks' => 0,
        ]);

        return $this->buildShortUrl($shortCode);
    }

    /**
     * Generate a unique short code
     *
     * @return string
     */
    protected function generateUniqueCode(): string
    {
        do {
            // Generate a 6-character alphanumeric code
            $code = Str::random(6);
        } while (ShortUrl::where('short_code', $code)->exists());

        return $code;
    }

    /**
     * Build the full short URL from a code
     *
     * @param string $code
     * @return string
     */
    protected function buildShortUrl(string $code): string
    {
        return url("/s/{$code}");
    }

    /**
     * Resolve a short code to its long URL
     *
     * @param string $code
     * @return string|null
     */
    public function resolve(string $code): ?string
    {
        // Try exact match first (case-sensitive)
        $record = ShortUrl::where('short_code', $code)->first();

        // Fallback to case-insensitive if not found (optional, helps with case-flattening)
        if (!$record) {
            $record = ShortUrl::whereRaw('LOWER(short_code) = LOWER(?)', [$code])->first();
        }

        if ($record) {
            if ($record->isExpired()) {
                Log::warning("Short URL resolution failed: Code '{$code}' has expired.", [
                    'short_code' => $record->short_code,
                    'long_url' => $record->long_url,
                    'expires_at' => $record->expires_at
                ]);
                return null;
            }

            $record->increment('clicks');
            $record->update(['last_accessed_at' => now()]);

            return $record->long_url;
        }

        Log::warning("Short URL resolution failed: Code '{$code}' not found in database.");

        return null;
    }
}

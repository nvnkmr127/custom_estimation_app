<?php

namespace App\Services;

use App\Models\ShortUrl;
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
        $shortUrl = ShortUrl::where('short_code', $code)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$shortUrl) {
            return null;
        }

        // Increment click count
        $shortUrl->increment('clicks');
        $shortUrl->update(['last_accessed_at' => now()]);

        return $shortUrl->long_url;
    }
}

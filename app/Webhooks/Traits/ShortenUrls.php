<?php

namespace App\Webhooks\Traits;

use App\Services\UrlShortenerService;

trait ShortenUrls
{
    /**
     * Shorten a URL using the URL shortener service
     *
     * @param string $url
     * @param int|\DateTimeInterface|null $expiry
     * @return string
     */
    protected function shortenUrl(string $url, $expiry = 30): string
    {
        $urlShortener = app(UrlShortenerService::class);
        return $urlShortener->shorten($url, $expiry);
    }
}

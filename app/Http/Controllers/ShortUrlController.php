<?php

namespace App\Http\Controllers;

use App\Services\UrlShortenerService;
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
            abort(404, 'Short URL not found or has expired');
        }

        return redirect($longUrl);
    }
}

<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken as Middleware;
use Closure;

class CustomValidateCsrfToken extends Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->bearerToken() || ($request->wantsJson() && ($request->is('login') || $request->routeIs('login')))) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}

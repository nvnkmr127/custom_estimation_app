<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiSessionAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($token = $request->bearerToken()) {
            $request->cookies->set(config('session.cookie'), $token);
        }

        if ($request->wantsJson() && $request->routeIs('estimates.show')) {
            $estimate = $request->route('estimate');
            if (!($estimate instanceof \App\Models\Estimate)) {
                $estimate = \App\Models\Estimate::findOrFail($estimate);
            }
            return app(\App\Http\Controllers\EstimateController::class)->show($estimate);
        }

        return $next($request);
    }
}

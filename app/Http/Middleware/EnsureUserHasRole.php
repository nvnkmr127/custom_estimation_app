<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Security: Super Admin has global bypass for all role-restricted routes
        if ($request->user() && $request->user()->hasRole('super_admin')) {
            return $next($request);
        }

        if (! $request->user() || ! $request->user()->hasRole($roles)) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}

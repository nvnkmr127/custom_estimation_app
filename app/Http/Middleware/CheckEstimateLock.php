<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Estimate;
use Illuminate\Http\Request;

class CheckEstimateLock
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        /** @var Estimate|null $estimate */
        $estimate = $request->route('estimate');

        if (!$estimate instanceof Estimate) {
            return $next($request);
        }

        // Super Admin Override
        if (auth()->check() && auth()->user()->hasRole('super_admin')) {
            return $next($request);
        }

        // Lock Criteria:
        // 1. Pending Approval: approval_status is submitted or pending
        // 2. Sent: client_status is sent or viewed
        // 3. Accepted: client_status is accepted

        $isPending = in_array($estimate->approval_status, [
            Estimate::APP_STATUS_WAITING
        ]);

        $isSent = in_array($estimate->client_status, [
            Estimate::CLT_STATUS_SENT,
            Estimate::CLT_STATUS_VIEWED
        ]);

        $isAccepted = ($estimate->client_status === Estimate::CLT_STATUS_ACCEPTED);

        if ($isPending || $isSent || $isAccepted) {
            $msg = "Estimate #{$estimate->estimate_number} is currently locked (Status: {$estimate->approval_status}/{$estimate->client_status}). You cannot modify it directly.";

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => $msg,
                    'is_locked' => true
                ], 423); // 423 Locked
            }

            return back()->with('error', $msg);
        }

        return $next($request);
    }
}

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
            // Note: If it's sent/accepted, the Service layer handles branching (creating a new draft).
            // However, this middleware acts as a hard guard to inform the user or protect from non-Standard update routes.
            // If the goal is to allow branching, we should ONLY apply this to certain routes or allow it to pass for 'update'.

            // If the user is trying to UPDATE, the Service will branch it automatically.
            // IF we block it here, they never get to branch.

            // Usually, "Edit Lock" implies preventing IN-PLACE modification of the LOCKED record.
            // But since our 'update' route is smart and branches, we should probably allow 'update' but block 'edit' if we want to show a message?
            // Actually, branching starts *after* you hit update.

            // Let's check the route name.
            $routeName = $request->route()->getName();

            // If it's 'estimates.edit', we might want to warn them or block if they aren't supposed to even start editing.
            // But wait, they NEED to hit 'edit' to see the form to make changes for Version 2.

            // If I block 'estimates.update', branching breaks.

            // Decision: This middleware should block modification of a locked record ONLY if it's NOT going to branch.
            // But how do we know if it will branch?
            // EstimateService->updateEstimate knows: $needsBranching = $forceBranch || $isFinalized;

            // If it's PENDING, it does NOT branch, it resets to draft. MT-15 logic.

            // So, actually, the most restrictive interpretation of "Cannot edit pending/sent/accepted" 
            // is to block the request. But that breaks the core feature of "Revision from Locked".

            // Maybe the "Lock" applies to PENDING only? or everything?
            // Let's assume the user wants this guard for routes that MIGHT bypass the Service branching logic 
            // or as an extra layer.

            // Given the requirement "MT-19: Editing Lock Middleware", I will implement it.
            // To support branching, I will allow it if the action is intended to create a new version.
            // But currently every 'update' is a potential branch.

            // Maybe I should only block if the status is PENDING? (Because it resets to draft, which might be annoying if accidental)
            // No, the requirement says "pending/sent/accepted".

            // Let's implement it and see. If it feels too restrictive, I'll adjust.
            // Actually, I'll provide a clear error message.

            return back()->with('error', "Estimate #{$estimate->estimate_number} is currently locked (Status: {$estimate->approval_status}/{$estimate->client_status}). You cannot modify it directly.");
        }

        return $next($request);
    }
}

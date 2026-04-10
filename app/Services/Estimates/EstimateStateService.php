<?php

namespace App\Services\Estimates;

use App\Models\Estimate;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class EstimateStateService
{
    /**
     * Define internal lifecycle transitions (Main Status).
     */
    protected array $estimateTransitions = [
        Estimate::EST_STATUS_DRAFT => [Estimate::EST_STATUS_PENDING_APPROVAL],
        Estimate::EST_STATUS_PENDING_APPROVAL => [Estimate::EST_STATUS_APPROVED, Estimate::EST_STATUS_DRAFT, Estimate::EST_STATUS_DECLINED],
        Estimate::EST_STATUS_APPROVED => [Estimate::EST_STATUS_SENT],
        Estimate::EST_STATUS_SENT => [Estimate::EST_STATUS_ACCEPTED, Estimate::EST_STATUS_DECLINED, Estimate::EST_STATUS_EXPIRED],
        Estimate::EST_STATUS_ACCEPTED => [],
        Estimate::EST_STATUS_DECLINED => [],
        Estimate::EST_STATUS_EXPIRED => [],
    ];

    /**
     * Define approval workflow transitions.
     */
    protected array $approvalTransitions = [
        Estimate::APP_STATUS_NOT_REQUIRED => [Estimate::APP_STATUS_WAITING, Estimate::APP_STATUS_APPROVED],
        Estimate::APP_STATUS_WAITING => [Estimate::APP_STATUS_APPROVED, Estimate::APP_STATUS_REJECTED, Estimate::APP_STATUS_CHANGES_REQUESTED, Estimate::APP_STATUS_NOT_REQUIRED],
        Estimate::APP_STATUS_APPROVED => [Estimate::APP_STATUS_NOT_REQUIRED],
        Estimate::APP_STATUS_REJECTED => [Estimate::APP_STATUS_NOT_REQUIRED],
        Estimate::APP_STATUS_CHANGES_REQUESTED => [Estimate::APP_STATUS_WAITING, Estimate::APP_STATUS_NOT_REQUIRED],
    ];

    /**
     * Define client interaction transitions.
     */
    protected array $clientTransitions = [
        Estimate::CLT_STATUS_NOT_SENT => [Estimate::CLT_STATUS_SENT],
        Estimate::CLT_STATUS_SENT => [Estimate::CLT_STATUS_VIEWED, Estimate::CLT_STATUS_ACCEPTED, Estimate::CLT_STATUS_DECLINED, Estimate::CLT_STATUS_EXPIRED, Estimate::CLT_STATUS_NOT_SENT],
        Estimate::CLT_STATUS_VIEWED => [Estimate::CLT_STATUS_ACCEPTED, Estimate::CLT_STATUS_DECLINED, Estimate::CLT_STATUS_NOT_SENT],
        Estimate::CLT_STATUS_ACCEPTED => [Estimate::CLT_STATUS_NOT_SENT],
        Estimate::CLT_STATUS_DECLINED => [Estimate::CLT_STATUS_NOT_SENT],
        Estimate::CLT_STATUS_EXPIRED => [Estimate::CLT_STATUS_SENT, Estimate::CLT_STATUS_NOT_SENT],
    ];

    /**
     * Transition the estimate lifecycle status.
     *
     * @param Estimate $estimate
     * @param string $newStatus
     * @return Estimate
     * @throws \InvalidArgumentException
     */
    public function transitionEstimateStatus(Estimate $estimate, string $newStatus): Estimate
    {
        return $this->performTransition($estimate, 'estimate_status', $newStatus, $this->estimateTransitions);
    }

    /**
     * Transition the approval workflow status.
     *
     * @param Estimate $estimate
     * @param string $newStatus
     * @return Estimate
     * @throws \InvalidArgumentException
     */
    public function transitionApprovalStatus(Estimate $estimate, string $newStatus): Estimate
    {
        return $this->performTransition($estimate, 'approval_status', $newStatus, $this->approvalTransitions);
    }

    /**
     * Transition the client interaction status.
     *
     * @param Estimate $estimate
     * @param string $newStatus
     * @return Estimate
     * @throws \InvalidArgumentException
     */
    public function transitionClientStatus(Estimate $estimate, string $newStatus, bool $force = false, array $extraData = []): Estimate
    {
        // Business Rule: Cannot send to client or mark as sent/viewed etc unless explicitly approved
        if (
            in_array($newStatus, [Estimate::CLT_STATUS_SENT, Estimate::CLT_STATUS_VIEWED, Estimate::CLT_STATUS_ACCEPTED])
            && $estimate->estimate_status !== Estimate::EST_STATUS_APPROVED
            && $estimate->estimate_status !== Estimate::EST_STATUS_SENT
        ) {
            throw new \InvalidArgumentException("Cannot transition client status to '{$newStatus}' because the estimate is not in an approved state.");
        }

        return $this->performTransition($estimate, 'client_status', $newStatus, $this->clientTransitions, $force, $extraData);
    }

    /**
     * Generic transition handler with validation, transactions, and logging.
     */
    protected function performTransition(Estimate $estimate, string $field, string $newStatus, array $matrix, bool $force = false, array $extraData = []): Estimate
    {
        $oldStatus = $estimate->{$field};

        if ($oldStatus === $newStatus && !$force) {
            return $estimate;
        }

        // Validate Transition
        $allowed = $matrix[$oldStatus] ?? [];

        // Admin Override
        $isAdmin = Auth::id()
            ? \App\Models\User::find(Auth::id())?->hasRole(['super_admin', 'admin'])
            : false;

        if (!in_array($newStatus, $allowed) && !$isAdmin && !($oldStatus === $newStatus && $force)) {
            throw new \InvalidArgumentException("Invalid transition for {$field}: '{$oldStatus}' to '{$newStatus}'.");
        }

        $result = DB::transaction(function () use ($estimate, $field, $newStatus, $oldStatus, $force, $extraData) {
            // Lock the record
            $lockedEstimate = Estimate::where('id', $estimate->id)->lockForUpdate()->firstOrFail();

            // Update Field
            $lockedEstimate->{$field} = $newStatus;

            // Apply Extra Data
            if (!empty($extraData)) {
                $lockedEstimate->fill($extraData);
            }

            unset($lockedEstimate->{$field});
            $lockedEstimate->{$field} = $newStatus;

            // Side Effects: Auto-update timestamps
            $this->applyTimestampSideEffects($lockedEstimate, $field, $newStatus);

            $lockedEstimate->save();

            // Log activity
            $action = ($oldStatus === $newStatus && $force) ? "re-applied" : "changed from '{$oldStatus}' to '{$newStatus}'";
            ActivityLog::log(
                'status_transition',
                $lockedEstimate,
                "Estimate #{$lockedEstimate->estimate_number} {$field} {$action}.",
                [
                    'field' => $field,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'forced' => $force
                ]
            );

            return $lockedEstimate;
        });

        // Sync back to the original object to avoid stale state in long-running processes or service sequences
        $estimate->{$field} = $result->{$field};
        $estimate->estimate_status = $result->estimate_status; // Sync side effect
        if ($field === 'client_status') {
            $estimate->sent_at = $result->sent_at;
            $estimate->expires_at = $result->expires_at;
            $estimate->accepted_at = $result->accepted_at;
            $estimate->declined_at = $result->declined_at;
        }

        return $result;
    }

    /**
     * Apply timestamp side effects based on status transitions.
     */
    protected function applyTimestampSideEffects(Estimate $estimate, string $field, string $newStatus): void
    {
        if ($field === 'client_status') {
            switch ($newStatus) {
                case Estimate::CLT_STATUS_SENT:
                    $estimate->sent_at = now();
                    $estimate->expires_at = now()->addDays(15);
                    $estimate->estimate_status = Estimate::EST_STATUS_SENT;
                    break;
                case Estimate::CLT_STATUS_VIEWED:
                    $estimate->estimate_status = Estimate::EST_STATUS_SENT; // Keep as sent
                    break;
                case Estimate::CLT_STATUS_ACCEPTED:
                    $estimate->accepted_at = now();
                    $estimate->estimate_status = Estimate::EST_STATUS_ACCEPTED;
                    break;
                case Estimate::CLT_STATUS_DECLINED:
                    $estimate->declined_at = now();
                    $estimate->estimate_status = Estimate::EST_STATUS_DECLINED;
                    break;
                case Estimate::CLT_STATUS_EXPIRED:
                    $estimate->expires_at = now();
                    $estimate->estimate_status = Estimate::EST_STATUS_EXPIRED;
                    break;
            }
        }
    }

    /**
     * Extend the expiry of an estimate.
     */
    public function extendExpiry(Estimate $estimate, \Carbon\Carbon $newDate): Estimate
    {
        return DB::transaction(function () use ($estimate, $newDate) {
            $lockedEstimate = Estimate::where('id', $estimate->id)->lockForUpdate()->firstOrFail();

            $oldDate = $lockedEstimate->expires_at ? \Carbon\Carbon::parse($lockedEstimate->expires_at) : null;
            $lockedEstimate->expires_at = $newDate;
            $lockedEstimate->expiry_date = $newDate->toDateString(); // Sync legacy

            // Recovery Logic: If previously expired, move back to ACTIVE
            $movedBack = false;
            if ($lockedEstimate->estimate_status === Estimate::EST_STATUS_EXPIRED) {
                $lockedEstimate->estimate_status = Estimate::EST_STATUS_APPROVED; // Or SENT depending on flow, but APPROVED is safer as it might need re-sending
                $movedBack = true;
            }

            $lockedEstimate->save();

            ActivityLog::log(
                'expiry_overridden',
                $lockedEstimate,
                "Estimate #{$lockedEstimate->estimate_number} expiry extended to " . $newDate->format('M d, Y') . " by " . Auth::user()->name . ($movedBack ? " (State restored to Active)" : ""),
                [
                    'old_date' => $oldDate?->toDateTimeString(),
                    'new_date' => $newDate->toDateTimeString(),
                    'restored' => $movedBack
                ]
            );

            return $lockedEstimate;
        });
    }

    /**
     * Resets the entire workflow of an estimate to draft state.
     * Often used when an approved/sent estimate is edited.
     */
    public function resetSafetyWorkflow(Estimate $estimate): void
    {
        $estimate->estimate_status = Estimate::EST_STATUS_DRAFT;
        $estimate->approval_status = Estimate::APP_STATUS_NOT_REQUIRED;
        $estimate->client_status = Estimate::CLT_STATUS_NOT_SENT;

        // Clear side effects
        $estimate->sent_at = null;
        $estimate->expires_at = null;
        $estimate->approval_chain_id = null;

        if ($estimate->exists) {
            // Clear existing approvals if any
            $estimate->approvals()->delete();

            ActivityLog::log(
                'workflow_reset',
                $estimate,
                "Estimate #{$estimate->estimate_number} workflow reset to Draft due to modification."
            );
        }
    }
}

<?php

namespace App\Services\Estimates;

use App\Models\Estimate;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class EstimateStateService
{
    /**
     * Define internal lifecycle transitions.
     */
    protected array $estimateTransitions = [
        Estimate::EST_STATUS_DRAFT => [Estimate::EST_STATUS_ACTIVE, Estimate::EST_STATUS_VOID],
        Estimate::EST_STATUS_ACTIVE => [Estimate::EST_STATUS_EXPIRED, Estimate::EST_STATUS_VOID, Estimate::EST_STATUS_DRAFT],
        Estimate::EST_STATUS_EXPIRED => [Estimate::EST_STATUS_ACTIVE, Estimate::EST_STATUS_VOID],
        Estimate::EST_STATUS_VOID => [Estimate::EST_STATUS_DRAFT],
    ];

    /**
     * Define approval workflow transitions.
     */
    protected array $approvalTransitions = [
        Estimate::APP_STATUS_DRAFT => [Estimate::APP_STATUS_SUBMITTED, Estimate::APP_STATUS_APPROVED],
        Estimate::APP_STATUS_SUBMITTED => [Estimate::APP_STATUS_PENDING, Estimate::APP_STATUS_APPROVED, Estimate::APP_STATUS_REJECTED, Estimate::APP_STATUS_CHANGES_REQUESTED, Estimate::APP_STATUS_DRAFT],
        Estimate::APP_STATUS_PENDING => [Estimate::APP_STATUS_APPROVED, Estimate::APP_STATUS_REJECTED, Estimate::APP_STATUS_CHANGES_REQUESTED, Estimate::APP_STATUS_DRAFT],
        Estimate::APP_STATUS_APPROVED => [Estimate::APP_STATUS_DRAFT],
        Estimate::APP_STATUS_REJECTED => [Estimate::APP_STATUS_DRAFT],
        Estimate::APP_STATUS_CHANGES_REQUESTED => [Estimate::APP_STATUS_SUBMITTED, Estimate::APP_STATUS_DRAFT],
    ];

    /**
     * Define client interaction transitions.
     */
    protected array $clientTransitions = [
        Estimate::CLT_STATUS_DRAFT => [Estimate::CLT_STATUS_SENT],
        Estimate::CLT_STATUS_SENT => [Estimate::CLT_STATUS_VIEWED, Estimate::CLT_STATUS_ACCEPTED, Estimate::CLT_STATUS_DECLINED, Estimate::CLT_STATUS_DRAFT],
        Estimate::CLT_STATUS_VIEWED => [Estimate::CLT_STATUS_ACCEPTED, Estimate::CLT_STATUS_DECLINED, Estimate::CLT_STATUS_DRAFT],
        Estimate::CLT_STATUS_ACCEPTED => [Estimate::CLT_STATUS_SENT, Estimate::CLT_STATUS_DRAFT],
        Estimate::CLT_STATUS_DECLINED => [Estimate::CLT_STATUS_SENT, Estimate::CLT_STATUS_DRAFT],
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
        // Business Rule: Cannot send to client or mark as sent/viewed etc unless approved
        if (
            in_array($newStatus, [Estimate::CLT_STATUS_SENT, Estimate::CLT_STATUS_VIEWED, Estimate::CLT_STATUS_ACCEPTED])
            && $estimate->approval_status !== Estimate::APP_STATUS_APPROVED
        ) {

            // Allow if user is admin (override)
            if (!Auth::user()?->hasRole(['super_admin', 'admin'])) {
                throw new \InvalidArgumentException("Cannot transition client status to '{$newStatus}' because the estimate is not yet approved.");
            }
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
        $isAdmin = Auth::user()?->hasRole(['super_admin', 'admin']);

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
                    break;
                case Estimate::CLT_STATUS_ACCEPTED:
                    $estimate->accepted_at = now();
                    break;
                case Estimate::CLT_STATUS_DECLINED:
                    $estimate->declined_at = now();
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
                $lockedEstimate->estimate_status = Estimate::EST_STATUS_ACTIVE;
                $lockedEstimate->status = Estimate::STATUS_SENT; // Sync legacy
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
        $estimate->approval_status = Estimate::APP_STATUS_DRAFT;
        $estimate->client_status = Estimate::CLT_STATUS_DRAFT;

        // Clear side effects
        $estimate->sent_at = null;
        $estimate->expires_at = null;
        $estimate->status = Estimate::STATUS_DRAFT; // legacy sync
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

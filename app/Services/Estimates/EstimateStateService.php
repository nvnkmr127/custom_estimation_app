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
        Estimate::EST_STATUS_DRAFT => [Estimate::EST_STATUS_PENDING_APPROVAL, Estimate::EST_STATUS_APPROVED],
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
     * Define central policy rules for each state.
     * This is the SINGLE SOURCE OF TRUTH for UI and Backend.
     */
    protected array $statePolicy = [
        Estimate::EST_STATUS_DRAFT => [
            'can_edit' => true,
            'can_delete' => true,
            'can_approve' => false,
            'can_submit' => true,
            'next_states' => [Estimate::EST_STATUS_PENDING_APPROVAL, Estimate::EST_STATUS_APPROVED],
        ],
        Estimate::EST_STATUS_PENDING_APPROVAL => [
            'can_edit' => false,
            'can_delete' => false,
            'can_approve' => true,
            'can_submit' => false,
            'next_states' => [Estimate::EST_STATUS_APPROVED, Estimate::EST_STATUS_DRAFT, Estimate::EST_STATUS_DECLINED],
        ],
        Estimate::EST_STATUS_APPROVED => [
            'can_edit' => false, // Editing an approved estimate requires branching (handled by Service)
            'can_delete' => true,
            'can_approve' => false,
            'can_submit' => false,
            'can_send' => true,
            'next_states' => [Estimate::EST_STATUS_SENT],
        ],
        Estimate::EST_STATUS_SENT => [
            'can_edit' => false,
            'can_delete' => false,
            'can_approve' => false,
            'can_submit' => false,
            'can_send' => true, // Resend allowed
            'next_states' => [Estimate::EST_STATUS_ACCEPTED, Estimate::EST_STATUS_DECLINED, Estimate::EST_STATUS_EXPIRED],
        ],
        Estimate::EST_STATUS_ACCEPTED => [
            'can_edit' => false,
            'can_delete' => false,
            'can_approve' => false,
            'can_submit' => false,
            'next_states' => [],
        ],
        Estimate::EST_STATUS_DECLINED => [
            'can_edit' => false,
            'can_delete' => true,
            'can_approve' => false,
            'can_submit' => false,
            'next_states' => [],
        ],
        Estimate::EST_STATUS_EXPIRED => [
            'can_edit' => false,
            'can_delete' => true,
            'can_approve' => false,
            'can_submit' => false,
            'next_states' => [Estimate::EST_STATUS_SENT],
        ],
    ];

    /**
     * Get the dynamic policy for a specific estimate and user.
     * This combines STATE constraints and ROLE permissions.
     */
    public function getPolicy(Estimate $estimate, ?\App\Models\User $user = null): array
    {
        $user = $user ?? Auth::user();
        if (!$user) return [];

        $status = $estimate->estimate_status;
        $rules = $this->statePolicy[$status] ?? [
            'can_edit' => false,
            'can_delete' => false,
            'can_approve' => false,
            'can_submit' => false,
            'can_send' => false,
            'next_states' => [],
        ];

        // Role-Based Overlays
        // 1. Super Admin / Admin can do almost anything (but still respects critical state locks like 'declined' if wanted)
        $isPowerUser = $user->hasRole(['super_admin', 'admin', 'estimator_admin']);

        // 2. Draft Phase
        $canEdit = $rules['can_edit'] && ($isPowerUser || $user->id === $estimate->created_by);
        
        // 3. Approval Phase
        // can_approve is true if state allows it AND user is either an admin OR explicitly assigned to a pending approval
        $isAssigned = $estimate->approvals()->where('user_id', $user->id)->where('status', 'pending')->exists();
        $canApprove = $rules['can_approve'] && ($isPowerUser || $isAssigned);

        // 4. Send Phase
        $canSend = ($rules['can_send'] ?? false) && ($isPowerUser || $user->hasPermission('send_estimates'));

        // 5. Delete Phase
        $canDelete = $rules['can_delete'] && ($isPowerUser || $user->id === $estimate->created_by);

        return array_merge($rules, [
            'can_edit' => $canEdit,
            'can_approve' => $canApprove,
            'can_submit' => $rules['can_submit'] && ($isPowerUser || $user->id === $estimate->created_by),
            'can_send' => $canSend,
            'can_delete' => $canDelete,
            'is_locked' => $status === Estimate::EST_STATUS_PENDING_APPROVAL,
            'lock_reason' => ($status === Estimate::EST_STATUS_PENDING_APPROVAL) ? "This estimate is currently under internal approval." : null
        ]);
    }

    /**
     * Strongly validate if an action is allowed for the estimate's current state.
     */
    public function validateAction(Estimate $estimate, string $action): void
    {
        $policy = $this->getPolicy($estimate);
        
        // Admin Override for safety
        $isAdmin = Auth::id()
            ? \App\Models\User::find(Auth::id())?->hasRole(['super_admin', 'admin'])
            : false;

        if (!($policy[$action] ?? false) && !$isAdmin) {
            throw new \InvalidArgumentException("Action '{$action}' is not allowed for the current estimate state: '{$estimate->estimate_status}'.");
        }
    }

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
            ? \App\Models\User::find(Auth::id())?->hasRole(['super_admin', 'admin', 'estimator_admin'])
            : false;

        if (!in_array($newStatus, $allowed) && !$isAdmin && !($oldStatus === $newStatus && $force)) {
            throw new \InvalidArgumentException("Invalid transition for {$field}: '{$oldStatus}' to '{$newStatus}'.");
        }

        $result = DB::transaction(function () use ($estimate, $field, $newStatus, $oldStatus, $force, $extraData) {
            // Lock the record for Atomic update
            $lockedEstimate = Estimate::where('id', $estimate->id)->lockForUpdate()->firstOrFail();

            // CONCURRENCY PROTECTION: Increment lock_version on every state transition
            // This invalidates any stale views or simultaneous approval attempts.
            $lockedEstimate->lock_version += 1;

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
                    'forced' => $force,
                    'new_lock_version' => $lockedEstimate->lock_version
                ]
            );

            return $lockedEstimate;
        });

        // Refresh the original object from the database
        $estimate->refresh();

        return $estimate;
    }

    /**
     * Manually increment the lock version. 
     * Useful for non-status changes that should still invalidate current approvals (e.g. content edits).
     */
    public function incrementLockVersion(Estimate $estimate): void
    {
        DB::table('estimates')->where('id', $estimate->id)->increment('lock_version');
        $estimate->refresh();
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

            // Recovery Logic: If previously expired, move back to SENT (if it was sent) or APPROVED
            $movedBack = false;
            if ($lockedEstimate->estimate_status === Estimate::EST_STATUS_EXPIRED) {
                if ($lockedEstimate->sent_at) {
                    $lockedEstimate->estimate_status = Estimate::EST_STATUS_SENT;
                    $lockedEstimate->client_status = Estimate::CLT_STATUS_SENT;
                } else {
                    $lockedEstimate->estimate_status = Estimate::EST_STATUS_APPROVED;
                    $lockedEstimate->client_status = Estimate::CLT_STATUS_NOT_SENT;
                }
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

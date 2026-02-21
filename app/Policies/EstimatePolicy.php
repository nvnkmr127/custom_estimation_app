<?php

namespace App\Policies;

use App\Models\Estimate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EstimatePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, Estimate $estimate)
    {
        if ($user->hasRole(['super_admin', 'admin', 'estimator_admin'])) {
            return true;
        }

        if ($estimate->created_by == $user->id) {
            return true;
        }

        // Check if user is in approval chain
        if ($estimate->approvals()->where('user_id', $user->id)->exists()) {
            return true;
        }

        // Check manual followers (on this estimate OR its parent)
        if ($estimate->manualFollowers()->where('user_id', $user->id)->exists()) {
            return true;
        }

        if ($estimate->parent_id) {
            $parent = $estimate->parent;
            if ($parent && $parent->manualFollowers()->where('user_id', $user->id)->exists()) {
                return true;
            }
        }

        return false;
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, Estimate $estimate)
    {
        // 1. Lock if Finalized/Accepted/Sent
        $isLocked = in_array($estimate->client_status, [
            Estimate::CLT_STATUS_SENT,
            Estimate::CLT_STATUS_VIEWED,
            Estimate::CLT_STATUS_ACCEPTED,
            Estimate::CLT_STATUS_DECLINED
        ]) || in_array($estimate->approval_status, [
                Estimate::APP_STATUS_SUBMITTED,
                Estimate::APP_STATUS_PENDING
            ]);

        if ($isLocked && !$user->hasRole('super_admin')) {
            // Note: Service layer might branch if finalized, but Policy acts as the primary gate.
            return false;
        }

        // Legacy check fallback
        if ($estimate->status === Estimate::STATUS_WAITING_APPROVAL && !$user->hasRole('super_admin')) {
            return false;
        }

        if ($user->id == $estimate->created_by) {
            return true;
        }

        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        // Check follower edit permission
        if ($estimate->userCanEdit($user)) {
            return true;
        }

        // Check parent permissions if this is a proposal
        if ($estimate->parent_id) {
            $parent = $estimate->parent;
            if ($parent && $parent->userCanEdit($user)) {
                return true;
            }
        }

        return false;
    }

    public function delete(User $user, Estimate $estimate)
    {
        return $user->id === $estimate->created_by || $user->hasRole(['super_admin', 'admin']);
    }

    public function restore(User $user, Estimate $estimate)
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function forceDelete(User $user, Estimate $estimate)
    {
        return $user->hasRole(['super_admin']);
    }

    public function revertToDraft(User $user, Estimate $estimate)
    {
        return $this->update($user, $estimate);
    }

    public function extendExpiry(User $user, Estimate $estimate)
    {
        return $user->hasRole(['super_admin', 'admin', 'estimator_admin']);
    }
}

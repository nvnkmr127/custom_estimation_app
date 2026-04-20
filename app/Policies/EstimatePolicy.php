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
        $isLocked = in_array($estimate->estimate_status, [
            Estimate::EST_STATUS_APPROVED,
            Estimate::EST_STATUS_SENT,
            Estimate::EST_STATUS_ACCEPTED,
            Estimate::EST_STATUS_DECLINED,
            Estimate::EST_STATUS_EXPIRED
        ]) || in_array($estimate->approval_status, [
            Estimate::APP_STATUS_WAITING
        ]);

        if ($isLocked && !$user->hasRole(['super_admin', 'admin', 'estimator_admin'])) {
            // Note: Service layer handles branching for locked/finalized estimates.
            // We allow the policy to pass if the user is authorized to edit, 
            // even if locked, to facilitate the "Edit-to-Branch" workflow.
        }

        if ($user->id == $estimate->created_by) {
            return true;
        }

        if ($user->hasRole(['super_admin', 'admin', 'estimator_admin'])) {
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
        return $user->id === $estimate->created_by || $user->hasRole(['super_admin', 'admin', 'estimator_admin']);
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
        if ($user->id === $estimate->created_by) {
            return true;
        }

        return $user->hasRole(['super_admin', 'admin', 'estimator_admin']);
    }

    public function extendExpiry(User $user, Estimate $estimate)
    {
        return $user->hasRole(['super_admin', 'admin', 'estimator_admin']);
    }
}

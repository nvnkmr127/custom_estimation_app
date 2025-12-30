<?php

namespace App\Policies;

use App\Models\Estimate;
use App\Models\User;

class EstimatePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Estimate $estimate): bool
    {
        // Admins can view everything.
        if ($user->isAdmin()) {
            return true;
        }

        // User can view their own estimates.
        // Or if they are 'sales', they might be restricted to only theirs?
        // For now, let's allow viewing.
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['super_admin', 'estimator_admin', 'estimator', 'sales']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Estimate $estimate): bool
    {
        // Admins can update any estimate.
        if ($user->isAdmin()) {
            return true;
        }

        // Sales can only update their own estimates if they are not approved yet.
        return $user->id === $estimate->created_by && $estimate->status === Estimate::STATUS_DRAFT;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Estimate $estimate): bool
    {
        // Admins can delete any estimate.
        if ($user->isAdmin()) {
            return true;
        }

        // Sales can only delete their own DRAFT estimates.
        return $user->id === $estimate->created_by && $estimate->status === 'draft';
    }

    /**
     * Determine whether the user can approve the estimate.
     */
    public function approve(User $user, Estimate $estimate): bool
    {
        return $user->isAdmin();
    }
}

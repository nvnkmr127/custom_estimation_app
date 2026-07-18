<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Admins, the assignee, or the creator may act on a task.
     * Mirrors the scoping applied in TaskController@index.
     */
    protected function canAccess(User $user, Task $task): bool
    {
        return $user->isAdmin()
            || (int) $task->assigned_to === $user->id
            || (int) $task->created_by === $user->id;
    }

    public function viewAny(User $user): bool
    {
        return true; // index scopes results per user
    }

    public function view(User $user, Task $task): bool
    {
        return $this->canAccess($user, $task);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Task $task): bool
    {
        return $this->canAccess($user, $task);
    }

    public function delete(User $user, Task $task): bool
    {
        return $this->canAccess($user, $task);
    }
}

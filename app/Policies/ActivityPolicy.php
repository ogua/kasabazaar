<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * The audit trail is read-only evidence: it can be read with permission, never created,
 * edited or deleted through the UI (App\Models\Activity also refuses updates and deletes).
 */
class ActivityPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_activity::log');
    }

    public function view(User $user, Activity $activity): bool
    {
        return $user->can('view_activity::log');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Activity $activity): bool
    {
        return false;
    }

    public function delete(User $user, Activity $activity): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, Activity $activity): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, Activity $activity): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function replicate(User $user, Activity $activity): bool
    {
        return false;
    }

    public function reorder(User $user): bool
    {
        return false;
    }
}

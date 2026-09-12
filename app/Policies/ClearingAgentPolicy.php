<?php

namespace App\Policies;

use App\Models\ClearingAgent;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ClearingAgentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_clearing::agent');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ClearingAgent $clearingAgent): bool
    {
        return $user->can('view_clearing::agent');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_clearing::agent');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ClearingAgent $clearingAgent): bool
    {
        return $user->can('update_clearing::agent');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ClearingAgent $clearingAgent): bool
    {
        return $user->can('delete_clearing::agent');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_clearing::agent');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, ClearingAgent $clearingAgent): bool
    {
        return $user->can('force_delete_clearing::agent');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_clearing::agent');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, ClearingAgent $clearingAgent): bool
    {
        return $user->can('restore_clearing::agent');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_clearing::agent');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, ClearingAgent $clearingAgent): bool
    {
        return $user->can('replicate_clearing::agent');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_clearing::agent');
    }
}

<?php

namespace App\Policies;

use App\Models\User;
use App\Models\InvestmentWithdrawalRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvestmentWithdrawalRequestPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_investment::withdrawal::request');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, InvestmentWithdrawalRequest $investmentWithdrawalRequest): bool
    {
        return $user->can('view_investment::withdrawal::request');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_investment::withdrawal::request');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, InvestmentWithdrawalRequest $investmentWithdrawalRequest): bool
    {
        return $user->can('update_investment::withdrawal::request');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, InvestmentWithdrawalRequest $investmentWithdrawalRequest): bool
    {
        return $user->can('delete_investment::withdrawal::request');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_investment::withdrawal::request');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, InvestmentWithdrawalRequest $investmentWithdrawalRequest): bool
    {
        return $user->can('force_delete_investment::withdrawal::request');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_investment::withdrawal::request');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, InvestmentWithdrawalRequest $investmentWithdrawalRequest): bool
    {
        return $user->can('restore_investment::withdrawal::request');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_investment::withdrawal::request');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, InvestmentWithdrawalRequest $investmentWithdrawalRequest): bool
    {
        return $user->can('replicate_investment::withdrawal::request');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_investment::withdrawal::request');
    }
}

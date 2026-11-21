<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WalletTransaction;

class WalletTransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('ViewAny:WalletTransaction');
    }

    public function view(User $user, WalletTransaction $walletTransaction): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->hasAnyRole(['admin', 'finance']) && $user->can('View:WalletTransaction')) {
            return true;
        }

        return $user->hasRole('reseller')
            && $user->can('View:WalletTransaction')
            && (int) $walletTransaction->wallet->reseller_profile_id === (int) $user->resellerProfile?->getKey();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, WalletTransaction $walletTransaction): bool
    {
        return false;
    }

    public function delete(User $user, WalletTransaction $walletTransaction): bool
    {
        return false;
    }
}

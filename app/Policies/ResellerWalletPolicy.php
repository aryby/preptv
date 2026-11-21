<?php

namespace App\Policies;

use App\Models\ResellerWallet;
use App\Models\User;

class ResellerWalletPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('ViewAny:ResellerWallet');
    }

    public function view(User $user, ResellerWallet $resellerWallet): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->hasAnyRole(['admin', 'finance']) && $user->can('View:ResellerWallet')) {
            return true;
        }

        return $user->hasRole('reseller')
            && $user->can('View:ResellerWallet')
            && (int) $resellerWallet->reseller_profile_id === (int) $user->resellerProfile?->getKey();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ResellerWallet $resellerWallet): bool
    {
        return false;
    }

    public function delete(User $user, ResellerWallet $resellerWallet): bool
    {
        return false;
    }
}

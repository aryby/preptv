<?php

namespace App\Policies;

use App\Models\ResellerProfile;
use App\Models\User;

class ResellerProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('ViewAny:ResellerProfile');
    }

    public function view(User $user, ResellerProfile $resellerProfile): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->hasAnyRole(['admin', 'support', 'finance'])) {
            return $user->can('View:ResellerProfile');
        }

        return $user->hasRole('reseller')
            && $user->can('View:ResellerProfile')
            && (int) $resellerProfile->user_id === (int) $user->getKey();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('Create:ResellerProfile');
    }

    public function update(User $user, ResellerProfile $resellerProfile): bool
    {
        return $user->hasRole('super_admin')
            || ($user->hasRole('admin') && $user->can('Update:ResellerProfile'));
    }

    public function delete(User $user, ResellerProfile $resellerProfile): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}

<?php

namespace App\Policies;

use App\Models\ResellerPackage;
use App\Models\User;

class ResellerPackagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('ViewAny:ResellerPackage');
    }

    public function view(User $user, ResellerPackage $resellerPackage): bool
    {
        if ($user->hasRole('super_admin') || ($user->hasRole('admin') && $user->can('View:ResellerPackage'))) {
            return true;
        }

        return $user->hasRole('reseller')
            && $user->can('View:ResellerPackage')
            && (int) $resellerPackage->reseller_profile_id === (int) $user->resellerProfile?->getKey();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('Create:ResellerPackage');
    }

    public function update(User $user, ResellerPackage $resellerPackage): bool
    {
        return $user->hasRole('super_admin')
            || ($user->hasRole('admin') && $user->can('Update:ResellerPackage'));
    }

    public function delete(User $user, ResellerPackage $resellerPackage): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}

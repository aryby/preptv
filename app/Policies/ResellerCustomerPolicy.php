<?php

namespace App\Policies;

use App\Models\ResellerCustomer;
use App\Models\User;

class ResellerCustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('ViewAny:ResellerCustomer');
    }

    public function view(User $user, ResellerCustomer $resellerCustomer): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->hasAnyRole(['admin', 'support']) && $user->can('View:ResellerCustomer')) {
            return true;
        }

        return $user->hasRole('reseller')
            && $user->can('View:ResellerCustomer')
            && (int) $resellerCustomer->reseller_profile_id === (int) $user->resellerProfile?->getKey();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin')
            || $user->can('Create:ResellerCustomer')
            && ($user->hasRole('admin') || ($user->hasRole('reseller') && $user->resellerProfile()->exists()));
    }

    public function update(User $user, ResellerCustomer $resellerCustomer): bool
    {
        return $user->hasRole('super_admin')
            || ($user->hasRole('admin') && $user->can('Update:ResellerCustomer'));
    }

    public function delete(User $user, ResellerCustomer $resellerCustomer): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}

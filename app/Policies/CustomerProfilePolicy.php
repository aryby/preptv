<?php

namespace App\Policies;

use App\Models\CustomerProfile;
use App\Models\User;

class CustomerProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin')
            || $user->can('ViewAny:CustomerProfile');
    }

    public function view(User $user, CustomerProfile $customerProfile): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->hasAnyRole(['admin', 'support'])) {
            return $user->can('View:CustomerProfile');
        }

        return $user->hasRole('customer')
            && $user->can('View:CustomerProfile')
            && (int) $customerProfile->user_id === (int) $user->getKey();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('customer')
            && $user->can('Create:CustomerProfile')
            && $user->customerProfile()->doesntExist();
    }

    public function update(User $user, CustomerProfile $customerProfile): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->hasAnyRole(['admin', 'support'])) {
            return $user->can('Update:CustomerProfile');
        }

        return $user->hasRole('customer')
            && $user->can('Update:CustomerProfile')
            && (int) $customerProfile->user_id === (int) $user->getKey();
    }

    public function delete(User $user, CustomerProfile $customerProfile): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}

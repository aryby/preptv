<?php

namespace App\Policies;

use App\Models\DeviceAssignment;
use App\Models\User;

class DeviceAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('ViewAny:DeviceAssignment');
    }

    public function view(User $user, DeviceAssignment $deviceAssignment): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if (! $user->can('View:DeviceAssignment')) {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'support'])) {
            return true;
        }

        if ($user->hasRole('reseller')) {
            return (int) $deviceAssignment->subscription->reseller_profile_id
                === (int) $user->resellerProfile?->getKey();
        }

        return $user->hasRole('customer')
            && (int) $deviceAssignment->subscription->user_id === (int) $user->getKey();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('Create:DeviceAssignment');
    }

    public function update(User $user, DeviceAssignment $deviceAssignment): bool
    {
        return $user->hasRole('super_admin')
            || ($user->hasRole('admin') && $user->can('Update:DeviceAssignment'));
    }

    public function delete(User $user, DeviceAssignment $deviceAssignment): bool
    {
        return false;
    }
}

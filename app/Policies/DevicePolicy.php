<?php

namespace App\Policies;

use App\Models\Device;
use App\Models\User;

class DevicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('ViewAny:Device');
    }

    public function view(User $user, Device $device): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if (! $user->can('View:Device')) {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'support'])) {
            return true;
        }

        if ($user->hasRole('reseller')) {
            $resellerProfileId = $user->resellerProfile?->getKey();

            return $resellerProfileId !== null
                && $device->assignments()
                    ->whereHas('subscription', fn ($query) => $query->where('reseller_profile_id', $resellerProfileId))
                    ->exists();
        }

        if ($user->hasRole('customer')) {
            return (int) $device->owner_user_id === (int) $user->getKey()
                || $device->assignments()
                    ->whereHas('subscription', fn ($query) => $query->where('user_id', $user->getKey()))
                    ->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('Create:Device');
    }

    public function update(User $user, Device $device): bool
    {
        return $user->hasRole('super_admin')
            || ($user->hasRole('admin') && $user->can('Update:Device'));
    }

    public function delete(User $user, Device $device): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}

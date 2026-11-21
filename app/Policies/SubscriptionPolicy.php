<?php

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;

class SubscriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('ViewAny:Subscription');
    }

    public function view(User $user, Subscription $subscription): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if (! $user->can('View:Subscription')) {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'support'])) {
            return true;
        }

        if ($user->hasRole('reseller')) {
            return (int) $subscription->reseller_profile_id === (int) $user->resellerProfile?->getKey();
        }

        return $user->hasRole('customer')
            && (int) $subscription->user_id === (int) $user->getKey();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('Create:Subscription');
    }

    public function update(User $user, Subscription $subscription): bool
    {
        return $user->hasRole('super_admin')
            || ($user->hasRole('admin') && $user->can('Update:Subscription'));
    }

    public function delete(User $user, Subscription $subscription): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}

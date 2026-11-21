<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('ViewAny:Order');
    }

    public function view(User $user, Order $order): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if (! $user->can('View:Order')) {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'support', 'finance'])) {
            return true;
        }

        if ($user->hasRole('reseller')) {
            return (int) $order->reseller_profile_id === (int) $user->resellerProfile?->getKey();
        }

        return $user->hasRole('customer')
            && (int) $order->user_id === (int) $user->getKey();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('Create:Order');
    }

    public function update(User $user, Order $order): bool
    {
        return $user->hasRole('super_admin')
            || ($user->hasRole('admin') && $user->can('Update:Order'));
    }

    public function delete(User $user, Order $order): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}

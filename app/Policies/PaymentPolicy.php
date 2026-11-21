<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('ViewAny:Payment');
    }

    public function view(User $user, Payment $payment): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if (! $user->can('View:Payment')) {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'finance'])) {
            return true;
        }

        return $user->hasAnyRole(['customer', 'reseller'])
            && $user->can('View:Order')
            && app(OrderPolicy::class)->view($user, $payment->order);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('Create:Payment');
    }

    public function update(User $user, Payment $payment): bool
    {
        return false;
    }

    public function delete(User $user, Payment $payment): bool
    {
        return false;
    }
}

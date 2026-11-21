<?php

namespace App\Policies;

use App\Models\PaymentRefund;
use App\Models\User;

class PaymentRefundPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('ViewAny:PaymentRefund');
    }

    public function view(User $user, PaymentRefund $refund): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->hasAnyRole(['admin', 'finance']) && $user->can('View:PaymentRefund')) {
            return true;
        }

        return $user->hasAnyRole(['customer', 'reseller'])
            && $user->can('View:PaymentRefund')
            && app(PaymentPolicy::class)->view($user, $refund->payment);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('Create:PaymentRefund');
    }

    public function update(User $user, PaymentRefund $refund): bool
    {
        return false;
    }

    public function delete(User $user, PaymentRefund $refund): bool
    {
        return false;
    }
}

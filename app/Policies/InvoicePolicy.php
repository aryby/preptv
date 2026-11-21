<?php

namespace App\Policies;

use App\Domain\Billing\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('ViewAny:Invoice');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if (! $user->can('View:Invoice')) {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'finance'])) {
            return true;
        }

        if ($user->hasRole('reseller')) {
            return (int) $invoice->reseller_profile_id === (int) $user->resellerProfile?->getKey();
        }

        return $user->hasRole('customer')
            && (int) $invoice->user_id === (int) $user->getKey();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('Create:Invoice');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $invoice->status === InvoiceStatus::Draft
            && ($user->hasRole('super_admin') || $user->can('Update:Invoice'));
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}

<?php

namespace App\Policies;

use App\Domain\Billing\Enums\CreditNoteStatus;
use App\Models\CreditNote;
use App\Models\User;

class CreditNotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('ViewAny:CreditNote');
    }

    public function view(User $user, CreditNote $creditNote): bool
    {
        return $user->hasRole('super_admin')
            || (
                $user->can('View:CreditNote')
                && app(InvoicePolicy::class)->view($user, $creditNote->invoice)
            );
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('Create:CreditNote');
    }

    public function update(User $user, CreditNote $creditNote): bool
    {
        return $creditNote->status === CreditNoteStatus::Draft
            && ($user->hasRole('super_admin') || $user->can('Update:CreditNote'));
    }

    public function delete(User $user, CreditNote $creditNote): bool
    {
        return false;
    }
}

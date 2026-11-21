<?php

namespace App\Policies;

use App\Models\Currency;
use App\Models\User;

class CurrencyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('ViewAny:Currency');
    }

    public function view(User $user, Currency $currency): bool
    {
        return $user->hasRole('super_admin') || $user->can('View:Currency');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('Create:Currency');
    }

    public function update(User $user, Currency $currency): bool
    {
        return $user->hasRole('super_admin') || $user->can('Update:Currency');
    }

    public function delete(User $user, Currency $currency): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}

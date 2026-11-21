<?php

namespace App\Policies;

use App\Models\PlanPrice;
use App\Models\User;

class PlanPricePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('ViewAny:PlanPrice');
    }

    public function view(User $user, PlanPrice $planPrice): bool
    {
        return $user->hasRole('super_admin') || $user->can('View:PlanPrice');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('Create:PlanPrice');
    }

    public function update(User $user, PlanPrice $planPrice): bool
    {
        return false;
    }

    public function delete(User $user, PlanPrice $planPrice): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}

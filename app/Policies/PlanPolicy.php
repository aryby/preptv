<?php

namespace App\Policies;

use App\Models\Plan;
use App\Models\User;

class PlanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('ViewAny:Plan');
    }

    public function view(User $user, Plan $plan): bool
    {
        return $user->hasRole('super_admin') || $user->can('View:Plan');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('Create:Plan');
    }

    public function update(User $user, Plan $plan): bool
    {
        return $user->hasRole('super_admin') || $user->can('Update:Plan');
    }

    public function delete(User $user, Plan $plan): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}

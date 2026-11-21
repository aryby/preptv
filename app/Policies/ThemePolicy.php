<?php

namespace App\Policies;

use App\Models\Theme;
use App\Models\User;

class ThemePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('ViewAny:Theme');
    }

    public function view(User $user, Theme $theme): bool
    {
        return $user->hasRole('super_admin') || $user->can('View:Theme');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('Create:Theme');
    }

    public function update(User $user, Theme $theme): bool
    {
        return $user->hasRole('super_admin') || $user->can('Update:Theme');
    }

    public function delete(User $user, Theme $theme): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}

<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('ViewAny:User');
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasRole('super_admin')
            || $user->getKey() === $model->getKey()
            || $user->can('View:User');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('Create:User');
    }

    public function update(User $user, User $model): bool
    {
        return $user->getKey() === $model->getKey()
            || $user->hasRole('super_admin')
            || $user->can('Update:User');
    }

    public function assignRoles(User $user, User $model): bool
    {
        return $user->hasRole('super_admin')
            || $user->can('Manage:UserRoles');
    }

    public function delete(User $user, User $model): bool
    {
        if ($model->hasRole('super_admin')) {
            return $user->hasRole('super_admin')
                && User::role('super_admin')->count() > 1
                && $user->can('Delete:User');
        }

        return $user->hasRole('super_admin') || $user->can('Delete:User');
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, User $model): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}

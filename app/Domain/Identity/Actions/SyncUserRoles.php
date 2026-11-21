<?php

namespace App\Domain\Identity\Actions;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class SyncUserRoles
{
    /**
     * @param  list<string>  $roleNames
     */
    public function execute(User $actor, User $target, array $roleNames): void
    {
        if (! $actor->can('assignRoles', $target)) {
            throw new AuthorizationException;
        }

        $roleNames = array_values(array_unique($roleNames));

        if (in_array('super_admin', $roleNames, true) && ! $actor->hasRole('super_admin')) {
            throw new AuthorizationException('Only a super administrator may grant the super_admin role.');
        }

        if ($target->hasRole('super_admin') && ! $actor->hasRole('super_admin')) {
            throw new AuthorizationException('Only a super administrator may change another super administrator roles.');
        }

        DB::transaction(function () use ($roleNames, $target): void {
            $superAdminRole = null;

            if ($target->hasRole('super_admin') || in_array('super_admin', $roleNames, true)) {
                $superAdminRole = Role::query()
                    ->where('name', 'super_admin')
                    ->where('guard_name', 'web')
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $roles = Role::query()
                ->where('guard_name', 'web')
                ->whereIn('name', $roleNames)
                ->get();

            if ($roles->count() !== count($roleNames)) {
                throw ValidationException::withMessages([
                    'roles' => 'One or more selected roles are invalid.',
                ]);
            }

            $target->unsetRelation('roles');

            if (
                $target->hasRole('super_admin')
                && ! in_array('super_admin', $roleNames, true)
                && ! DB::table('model_has_roles')
                    ->where('role_id', $superAdminRole->getKey())
                    ->where('model_type', $target->getMorphClass())
                    ->where('model_id', '!=', $target->getKey())
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'roles' => 'The last super administrator cannot be demoted.',
                ]);
            }

            $target->syncRoles($roles);
        });
    }
}

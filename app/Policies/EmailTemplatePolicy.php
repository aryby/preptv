<?php

namespace App\Policies;

use App\Models\EmailTemplate;
use App\Models\User;

class EmailTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('ViewAny:EmailTemplate');
    }

    public function view(User $user, EmailTemplate $emailTemplate): bool
    {
        return $user->hasRole('super_admin') || $user->can('View:EmailTemplate');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('Create:EmailTemplate');
    }

    public function update(User $user, EmailTemplate $emailTemplate): bool
    {
        return $user->hasRole('super_admin') || $user->can('Update:EmailTemplate');
    }

    public function delete(User $user, EmailTemplate $emailTemplate): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}

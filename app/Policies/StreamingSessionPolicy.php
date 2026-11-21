<?php

namespace App\Policies;

use App\Models\StreamingSession;
use App\Models\User;

class StreamingSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('ViewAny:StreamingSession');
    }

    public function view(User $user, StreamingSession $streamingSession): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if (! $user->can('View:StreamingSession')) {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'support'])) {
            return true;
        }

        if ($user->hasRole('reseller')) {
            return (int) $streamingSession->subscription->reseller_profile_id
                === (int) $user->resellerProfile?->getKey();
        }

        return $user->hasRole('customer')
            && (int) $streamingSession->subscription->user_id === (int) $user->getKey();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, StreamingSession $streamingSession): bool
    {
        return false;
    }

    public function delete(User $user, StreamingSession $streamingSession): bool
    {
        return false;
    }
}

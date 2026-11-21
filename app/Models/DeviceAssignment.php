<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeviceAssignment extends Model
{
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function streamingSessions(): HasMany
    {
        return $this->hasMany(StreamingSession::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->hasAnyRole(['super_admin', 'admin', 'support'])) {
            return;
        }

        if ($user->hasRole('reseller')) {
            $resellerProfileId = $user->resellerProfile?->getKey();

            if ($resellerProfileId !== null) {
                $query->whereHas('subscription', fn (Builder $subscriptionQuery) => $subscriptionQuery->where('reseller_profile_id', $resellerProfileId));

                return;
            }
        } elseif ($user->hasRole('customer')) {
            $query->whereHas('subscription', fn (Builder $subscriptionQuery) => $subscriptionQuery->where('user_id', $user->getKey()));

            return;
        }

        $query->whereRaw('1 = 0');
    }
}

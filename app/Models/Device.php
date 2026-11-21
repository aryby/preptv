<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    protected $fillable = [
        'label',
        'device_type',
    ];

    protected $hidden = [
        'fingerprint_hash',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(DeviceAssignment::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->hasAnyRole(['super_admin', 'admin', 'support'])) {
            return;
        }

        if ($user->hasRole('reseller')) {
            $resellerProfileId = $user->resellerProfile?->getKey();

            if ($resellerProfileId === null) {
                $query->whereRaw('1 = 0');

                return;
            }

            $query->whereHas('assignments.subscription', function (Builder $subscriptionQuery) use ($resellerProfileId): void {
                $subscriptionQuery->where('reseller_profile_id', $resellerProfileId);
            });

            return;
        }

        if ($user->hasRole('customer')) {
            $query->where(function (Builder $deviceQuery) use ($user): void {
                $deviceQuery
                    ->where('owner_user_id', $user->getKey())
                    ->orWhereHas('assignments.subscription', function (Builder $subscriptionQuery) use ($user): void {
                        $subscriptionQuery->where('user_id', $user->getKey());
                    });
            });

            return;
        }

        $query->whereRaw('1 = 0');
    }
}

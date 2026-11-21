<?php

namespace App\Models;

use App\Domain\Sessions\Enums\StreamingSessionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StreamingSession extends Model
{
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'status' => StreamingSessionStatus::class,
            'started_at' => 'datetime',
            'heartbeat_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function deviceAssignment(): BelongsTo
    {
        return $this->belongsTo(DeviceAssignment::class);
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

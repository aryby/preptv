<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResellerCustomer extends Model
{
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
        ];
    }

    public function resellerProfile(): BelongsTo
    {
        return $this->belongsTo(ResellerProfile::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_user_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->hasAnyRole(['super_admin', 'admin', 'support'])) {
            return;
        }

        $resellerProfileId = $user->resellerProfile?->getKey();

        if ($user->hasRole('reseller') && $resellerProfileId !== null) {
            $query->where('reseller_profile_id', $resellerProfileId);

            return;
        }

        $query->whereRaw('1 = 0');
    }
}

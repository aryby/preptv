<?php

namespace App\Models;

use App\Domain\Resellers\Enums\ResellerStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResellerProfile extends Model
{
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'status' => ResellerStatus::class,
            'commission_basis_points' => 'integer',
            'archived_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(ResellerCustomer::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(ResellerPackage::class);
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(ResellerWallet::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->hasAnyRole(['super_admin', 'admin', 'support', 'finance'])) {
            return;
        }

        if ($user->hasRole('reseller')) {
            $query->where('user_id', $user->getKey());

            return;
        }

        $query->whereRaw('1 = 0');
    }
}

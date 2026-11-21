<?php

namespace App\Models;

use App\Domain\Resellers\Enums\WalletTransactionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'balance_after_minor' => 'integer',
            'type' => WalletTransactionType::class,
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(ResellerWallet::class, 'reseller_wallet_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->hasAnyRole(['super_admin', 'admin', 'finance'])) {
            return;
        }

        $resellerProfileId = $user->resellerProfile?->getKey();

        if ($user->hasRole('reseller') && $resellerProfileId !== null) {
            $query->whereHas('wallet', function (Builder $walletQuery) use ($resellerProfileId): void {
                $walletQuery->where('reseller_profile_id', $resellerProfileId);
            });

            return;
        }

        $query->whereRaw('1 = 0');
    }
}

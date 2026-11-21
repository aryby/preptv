<?php

namespace App\Models;

use App\Domain\Payments\Enums\PaymentRefundStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRefund extends Model
{
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'status' => PaymentRefundStatus::class,
            'amount_minor' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
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

        if ($user->hasRole('reseller')) {
            $resellerProfileId = $user->resellerProfile?->getKey();

            if ($resellerProfileId !== null) {
                $query->whereHas('payment.order', fn (Builder $orderQuery) => $orderQuery->where('reseller_profile_id', $resellerProfileId));

                return;
            }
        } elseif ($user->hasRole('customer')) {
            $query->whereHas('payment.order', fn (Builder $orderQuery) => $orderQuery->where('user_id', $user->getKey()));

            return;
        }

        $query->whereRaw('1 = 0');
    }
}

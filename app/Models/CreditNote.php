<?php

namespace App\Models;

use App\Domain\Billing\Enums\CreditNoteStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditNote extends Model
{
    protected $guarded = ['*'];

    protected $hidden = [
        'buyer_snapshot',
        'seller_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'status' => CreditNoteStatus::class,
            'amount_minor' => 'integer',
            'tax_minor' => 'integer',
            'total_minor' => 'integer',
            'buyer_snapshot' => 'array',
            'seller_snapshot' => 'array',
            'issued_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CreditNoteItem::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->hasAnyRole(['super_admin', 'admin', 'finance'])) {
            return;
        }

        if ($user->hasRole('reseller')) {
            $resellerProfileId = $user->resellerProfile?->getKey();

            if ($resellerProfileId !== null) {
                $query->whereHas('invoice', fn (Builder $invoiceQuery) => $invoiceQuery->where('reseller_profile_id', $resellerProfileId));

                return;
            }
        } elseif ($user->hasRole('customer')) {
            $query->whereHas('invoice', fn (Builder $invoiceQuery) => $invoiceQuery->where('user_id', $user->getKey()));

            return;
        }

        $query->whereRaw('1 = 0');
    }
}

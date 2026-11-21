<?php

namespace App\Models;

use App\Domain\Billing\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class Invoice extends Model
{
    protected $guarded = ['*'];

    protected $hidden = [
        'buyer_snapshot',
        'seller_snapshot',
        'pdf_path',
        'pdf_sha256',
    ];

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'subtotal_minor' => 'integer',
            'tax_minor' => 'integer',
            'total_minor' => 'integer',
            'buyer_snapshot' => 'array',
            'seller_snapshot' => 'array',
            'issued_at' => 'datetime',
            'due_at' => 'datetime',
            'paid_at' => 'datetime',
            'pdf_generated_at' => 'datetime',
            'email_sent_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (Invoice $invoice): void {
            $immutableAttributes = [
                'invoice_number',
                'order_id',
                'subscription_id',
                'user_id',
                'reseller_profile_id',
                'currency_code',
                'status',
                'subtotal_minor',
                'tax_minor',
                'total_minor',
                'buyer_snapshot',
                'seller_snapshot',
                'issued_at',
                'due_at',
                'paid_at',
            ];

            if (
                $invoice->getRawOriginal('status') !== InvoiceStatus::Draft->value
                && $invoice->isDirty($immutableAttributes)
            ) {
                throw new LogicException('An issued invoice is immutable; correct it with a credit note.');
            }
        });

        static::deleting(function (Invoice $invoice): void {
            if ($invoice->status !== InvoiceStatus::Draft) {
                throw new LogicException('An issued invoice cannot be deleted.');
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resellerProfile(): BelongsTo
    {
        return $this->belongsTo(ResellerProfile::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNote::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->hasAnyRole(['super_admin', 'admin', 'finance'])) {
            return;
        }

        if ($user->hasRole('reseller')) {
            $resellerProfileId = $user->resellerProfile?->getKey();

            if ($resellerProfileId === null) {
                $query->whereRaw('1 = 0');

                return;
            }

            $query->where('reseller_profile_id', $resellerProfileId);

            return;
        }

        if ($user->hasRole('customer')) {
            $query->where('user_id', $user->getKey());

            return;
        }

        $query->whereRaw('1 = 0');
    }
}

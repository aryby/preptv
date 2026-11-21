<?php

namespace App\Models;

use App\Domain\Billing\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class InvoiceItem extends Model
{
    protected $guarded = ['*'];

    protected static function booted(): void
    {
        static::saving(function (InvoiceItem $item): void {
            if ($item->invoice?->status !== InvoiceStatus::Draft) {
                throw new LogicException('An issued invoice line is immutable.');
            }
        });

        static::deleting(function (InvoiceItem $item): void {
            if ($item->invoice?->status !== InvoiceStatus::Draft) {
                throw new LogicException('An issued invoice line cannot be deleted.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_amount_minor' => 'integer',
            'subtotal_minor' => 'integer',
            'tax_rate_basis_points' => 'integer',
            'tax_minor' => 'integer',
            'total_minor' => 'integer',
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

    public function creditNoteItems(): HasMany
    {
        return $this->hasMany(CreditNoteItem::class);
    }
}

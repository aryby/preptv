<?php

namespace App\Models;

use App\Domain\Billing\Enums\CreditNoteStatus;
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
}

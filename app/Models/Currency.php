<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'minor_unit',
        'symbol',
        'is_active',
    ];

    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'minor_unit' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function planPrices(): HasMany
    {
        return $this->hasMany(PlanPrice::class, 'currency_code', 'code');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'currency_code', 'code');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'currency_code', 'code');
    }

    public function paymentRefunds(): HasMany
    {
        return $this->hasMany(PaymentRefund::class, 'currency_code', 'code');
    }

    public function resellerWallets(): HasMany
    {
        return $this->hasMany(ResellerWallet::class, 'currency_code', 'code');
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'currency_code', 'code');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'currency_code', 'code');
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'currency_code', 'code');
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNote::class, 'currency_code', 'code');
    }

    public function creditNoteItems(): HasMany
    {
        return $this->hasMany(CreditNoteItem::class, 'currency_code', 'code');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}

<?php

namespace App\Models;

use App\Domain\Orders\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $guarded = ['*'];

    protected $hidden = [
        'customer_snapshot',
        'seller_snapshot',
        'guest_access_token_hash',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal_minor' => 'integer',
            'tax_minor' => 'integer',
            'total_minor' => 'integer',
            'customer_snapshot' => 'array',
            'seller_snapshot' => 'array',
            'guest_access_expires_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resellerProfile(): BelongsTo
    {
        return $this->belongsTo(ResellerProfile::class);
    }

    public function resellerCustomer(): BelongsTo
    {
        return $this->belongsTo(ResellerCustomer::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function emailLogs(): HasMany
    {
        return $this->hasMany(EmailLog::class);
    }

    public function scopeForPublicId(Builder $query, string $publicId): void
    {
        $query->where('public_id', $publicId);
    }

    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->hasAnyRole(['super_admin', 'admin', 'support', 'finance'])) {
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

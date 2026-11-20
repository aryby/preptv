<?php

namespace App\Models;

use App\Domain\Catalog\Enums\BillingPeriodUnit;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanPrice extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'billing_period_unit' => BillingPeriodUnit::class,
            'billing_period_count' => 'integer',
            'amount_minor' => 'integer',
            'effective_from' => 'datetime',
            'effective_until' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function resellerPackages(): HasMany
    {
        return $this->hasMany(ResellerPackage::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function scopeEffectiveAt(Builder $query, DateTimeInterface $at): void
    {
        $query
            ->where('effective_from', '<=', $at)
            ->where(function (Builder $query) use ($at): void {
                $query->whereNull('effective_until')->orWhere('effective_until', '>=', $at);
            })
            ->whereNull('archived_at');
    }
}

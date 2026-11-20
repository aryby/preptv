<?php

namespace App\Models;

use App\Domain\Catalog\Enums\BillingPeriodUnit;
use App\Domain\Subscriptions\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'amount_minor' => 'integer',
            'billing_period_unit' => BillingPeriodUnit::class,
            'billing_period_count' => 'integer',
            'device_limit_snapshot' => 'integer',
            'concurrent_session_limit_snapshot' => 'integer',
            'starts_at' => 'datetime',
            'current_period_ends_at' => 'datetime',
            'next_renewal_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function planPrice(): BelongsTo
    {
        return $this->belongsTo(PlanPrice::class);
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

    public function histories(): HasMany
    {
        return $this->hasMany(SubscriptionHistory::class);
    }

    public function deviceAssignments(): HasMany
    {
        return $this->hasMany(DeviceAssignment::class);
    }

    public function streamingSessions(): HasMany
    {
        return $this->hasMany(StreamingSession::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}

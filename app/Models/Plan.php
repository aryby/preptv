<?php

namespace App\Models;

use App\Domain\Catalog\Enums\PlanStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'status',
        'is_public',
        'sort_order',
        'device_limit',
        'concurrent_session_limit',
    ];

    protected function casts(): array
    {
        return [
            'status' => PlanStatus::class,
            'is_public' => 'boolean',
            'sort_order' => 'integer',
            'device_limit' => 'integer',
            'concurrent_session_limit' => 'integer',
        ];
    }

    public function planPrices(): HasMany
    {
        return $this->hasMany(PlanPrice::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', PlanStatus::Active->value);
    }

    public function scopePublished(Builder $query): void
    {
        $query
            ->where('status', PlanStatus::Active->value)
            ->where('is_public', true);
    }
}

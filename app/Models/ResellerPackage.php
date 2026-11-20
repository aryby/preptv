<?php

namespace App\Models;

use App\Domain\Resellers\Enums\ResellerPackageStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerPackage extends Model
{
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'override_amount_minor' => 'integer',
            'commission_basis_points' => 'integer',
            'status' => ResellerPackageStatus::class,
            'effective_from' => 'datetime',
            'effective_until' => 'datetime',
        ];
    }

    public function resellerProfile(): BelongsTo
    {
        return $this->belongsTo(ResellerProfile::class);
    }

    public function planPrice(): BelongsTo
    {
        return $this->belongsTo(PlanPrice::class);
    }
}

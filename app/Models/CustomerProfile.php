<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerProfile extends Model
{
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'billing_address' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->hasAnyRole(['super_admin', 'admin', 'support'])) {
            return;
        }

        if ($user->hasRole('customer')) {
            $query->where('user_id', $user->getKey());

            return;
        }

        $query->whereRaw('1 = 0');
    }
}

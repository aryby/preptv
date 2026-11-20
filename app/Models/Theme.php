<?php

namespace App\Models;

use App\Domain\Themes\Enums\ThemeStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Theme extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => ThemeStatus::class,
            'archived_at' => 'datetime',
        ];
    }

    public function settings(): HasMany
    {
        return $this->hasMany(ThemeSetting::class);
    }
}

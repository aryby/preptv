<?php

namespace App\Models;

use App\Domain\Sessions\Enums\StreamingSessionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StreamingSession extends Model
{
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'status' => StreamingSessionStatus::class,
            'started_at' => 'datetime',
            'heartbeat_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function deviceAssignment(): BelongsTo
    {
        return $this->belongsTo(DeviceAssignment::class);
    }
}

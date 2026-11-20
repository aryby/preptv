<?php

namespace App\Models;

use App\Domain\Payments\Enums\PaymentWebhookEventStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentWebhookEvent extends Model
{
    protected $guarded = ['*'];

    protected $hidden = [
        'payload_hash',
        'sanitized_payload',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentWebhookEventStatus::class,
            'sanitized_payload' => 'array',
            'attempt_count' => 'integer',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}

<?php

namespace App\Domain\Payments\Actions;

use App\Domain\Payments\Enums\PaymentWebhookEventStatus;
use App\Models\PaymentWebhookEvent;
use Illuminate\Support\Facades\DB;

class RecordRejectedWebhook
{
    public function execute(string $payloadHash, string $transmissionId, string $failureCode): void
    {
        $eventId = 'rejected-'.hash('sha256', $transmissionId.'|'.$payloadHash);

        DB::table((new PaymentWebhookEvent)->getTable())->insertOrIgnore([
            'provider' => 'paypal',
            'provider_event_id' => $eventId,
            'payment_id' => null,
            'payload_hash' => $payloadHash,
            'sanitized_payload' => null,
            'status' => PaymentWebhookEventStatus::Rejected->value,
            'attempt_count' => 1,
            'received_at' => now(),
            'processed_at' => now(),
            'failure_code' => $failureCode,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

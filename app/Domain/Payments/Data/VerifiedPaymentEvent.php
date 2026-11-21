<?php

namespace App\Domain\Payments\Data;

use App\Domain\Payments\Enums\PaymentEventKind;

final readonly class VerifiedPaymentEvent
{
    /**
     * @param  array<string, mixed>|null  $sanitizedPayload
     */
    public function __construct(
        public string $provider,
        public string $providerEventId,
        public string $eventType,
        public PaymentEventKind $kind,
        public ?string $providerOrderId,
        public ?string $providerCaptureId,
        public ?string $providerRefundId,
        public ?int $amountMinor,
        public ?string $currencyCode,
        public ?string $providerStatus,
        public string $payloadHash,
        public ?array $sanitizedPayload,
    ) {}
}

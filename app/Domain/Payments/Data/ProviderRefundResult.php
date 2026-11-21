<?php

namespace App\Domain\Payments\Data;

use App\Domain\Payments\Enums\PaymentRefundStatus;

final readonly class ProviderRefundResult
{
    public function __construct(
        public string $providerRefundId,
        public PaymentRefundStatus $status,
    ) {}
}

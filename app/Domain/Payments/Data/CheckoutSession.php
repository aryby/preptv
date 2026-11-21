<?php

namespace App\Domain\Payments\Data;

final readonly class CheckoutSession
{
    public function __construct(
        public string $providerPaymentId,
        public string $approvalUrl,
    ) {}
}

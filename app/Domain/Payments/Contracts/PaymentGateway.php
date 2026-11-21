<?php

namespace App\Domain\Payments\Contracts;

use App\Domain\Payments\Data\CheckoutSession;
use App\Domain\Payments\Data\ProviderRefundResult;
use App\Domain\Payments\Data\VerifiedPaymentEvent;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentRefund;

interface PaymentGateway
{
    public function provider(): string;

    public function isConfigured(): bool;

    public function createCheckout(
        Payment $payment,
        Order $order,
        string $returnUrl,
        string $cancelUrl,
    ): CheckoutSession;

    public function captureApprovedPayment(Payment $payment, string $providerOrderId): ?string;

    /**
     * @param  array<string, string|null>  $headers
     */
    public function verifyWebhook(string $rawBody, array $headers): VerifiedPaymentEvent;

    public function refund(Payment $payment, PaymentRefund $refund): ProviderRefundResult;
}

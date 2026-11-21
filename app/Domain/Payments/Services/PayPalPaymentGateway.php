<?php

namespace App\Domain\Payments\Services;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Data\CheckoutSession;
use App\Domain\Payments\Data\ProviderRefundResult;
use App\Domain\Payments\Data\VerifiedPaymentEvent;
use App\Domain\Payments\Enums\PaymentEventKind;
use App\Domain\Payments\Enums\PaymentRefundStatus;
use App\Domain\Payments\Exceptions\InvalidWebhookPayload;
use App\Domain\Payments\Exceptions\InvalidWebhookSignature;
use App\Domain\Payments\Exceptions\PaymentGatewayException;
use App\Models\Currency;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentRefund;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use JsonException;

class PayPalPaymentGateway implements PaymentGateway
{
    public function provider(): string
    {
        return 'paypal';
    }

    public function isConfigured(): bool
    {
        return filled(config('services.paypal.client_id'))
            && filled(config('services.paypal.client_secret'))
            && filled(config('services.paypal.webhook_id'))
            && in_array(config('services.paypal.mode'), ['sandbox', 'live'], true);
    }

    public function createCheckout(
        Payment $payment,
        Order $order,
        string $returnUrl,
        string $cancelUrl,
    ): CheckoutSession {
        $this->ensureConfigured();

        if ($payment->amount_minor !== $order->total_minor || $payment->currency_code !== $order->currency_code) {
            throw new PaymentGatewayException('payment_snapshot_mismatch');
        }

        $currency = $order->currency ?? Currency::query()->findOrFail($order->currency_code);
        $response = $this->apiRequest('POST', '/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $order->public_id,
                'custom_id' => $order->public_id,
                'description' => 'Order '.$order->public_id,
                'amount' => [
                    'currency_code' => $order->currency_code,
                    'value' => $this->formatAmount($order->total_minor, $currency->minor_unit),
                ],
            ]],
            'application_context' => [
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
                'user_action' => 'PAY_NOW',
            ],
        ], [
            'PayPal-Request-Id' => $payment->idempotency_key,
        ]);

        $providerOrderId = $response['id'] ?? null;
        $approvalUrl = collect($response['links'] ?? [])
            ->first(fn (mixed $link): bool => is_array($link) && ($link['rel'] ?? null) === 'approve')['href'] ?? null;

        if (! is_string($providerOrderId) || ! is_string($approvalUrl) || ! str_starts_with($approvalUrl, 'https://')) {
            throw new PaymentGatewayException('paypal_invalid_checkout_response');
        }

        return new CheckoutSession($providerOrderId, $approvalUrl);
    }

    public function captureApprovedPayment(Payment $payment, string $providerOrderId): ?string
    {
        $this->ensureConfigured();

        if ($payment->provider_payment_id !== $providerOrderId) {
            throw new PaymentGatewayException('paypal_order_mismatch');
        }

        $order = $this->apiRequest('GET', '/v2/checkout/orders/'.rawurlencode($providerOrderId));
        $purchaseUnits = $order['purchase_units'] ?? [];
        $currency = $payment->currency ?? Currency::query()->findOrFail($payment->currency_code);

        if (
            ($order['id'] ?? null) !== $providerOrderId
            || ($order['status'] ?? null) !== 'APPROVED'
            || count($purchaseUnits) !== 1
            || ($purchaseUnits[0]['reference_id'] ?? null) !== $payment->order->public_id
            || ($purchaseUnits[0]['custom_id'] ?? null) !== $payment->order->public_id
            || ! $this->providerAmountMatches($purchaseUnits[0]['amount'] ?? null, $payment->amount_minor, $payment->currency_code, $currency->minor_unit)
        ) {
            throw new PaymentGatewayException('paypal_order_reconciliation_failed');
        }

        $captureResponse = $this->apiRequest(
            'POST',
            '/v2/checkout/orders/'.rawurlencode($providerOrderId).'/capture',
            [],
            ['PayPal-Request-Id' => substr(hash('sha256', 'capture:'.$payment->idempotency_key), 0, 36)],
        );

        $captureId = $captureResponse['purchase_units'][0]['payments']['captures'][0]['id'] ?? null;

        return is_string($captureId) ? $captureId : null;
    }

    public function verifyWebhook(string $rawBody, array $headers): VerifiedPaymentEvent
    {
        $this->ensureConfigured();

        try {
            $payload = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidWebhookPayload(previous: $exception);
        }

        if (
            ! is_array($payload)
            || ! is_string($payload['id'] ?? null)
            || ! is_string($payload['event_type'] ?? null)
            || ! is_array($payload['resource'] ?? null)
        ) {
            throw new InvalidWebhookPayload;
        }

        $requiredHeaders = [
            'paypal-auth-algo' => 'auth_algo',
            'paypal-cert-url' => 'cert_url',
            'paypal-transmission-id' => 'transmission_id',
            'paypal-transmission-sig' => 'transmission_sig',
            'paypal-transmission-time' => 'transmission_time',
        ];
        $paypalHeaders = [];

        foreach ($requiredHeaders as $header => $field) {
            $value = $headers[$header] ?? null;

            if (! is_string($value) || $value === '') {
                throw new InvalidWebhookSignature;
            }

            $paypalHeaders[$field] = $value;
        }

        $verification = $this->apiRequest('POST', '/v1/notifications/verify-webhook-signature', [
            ...$paypalHeaders,
            'webhook_id' => config('services.paypal.webhook_id'),
            'webhook_event' => $payload,
        ]);

        if (($verification['verification_status'] ?? null) !== 'SUCCESS') {
            throw new InvalidWebhookSignature;
        }

        return $this->normalizeEvent($payload, hash('sha256', $rawBody));
    }

    public function refund(Payment $payment, PaymentRefund $refund): ProviderRefundResult
    {
        $this->ensureConfigured();

        if ($payment->provider_capture_id === null || $refund->currency_code !== $payment->currency_code) {
            throw new PaymentGatewayException('paypal_capture_unavailable');
        }

        $currency = $payment->currency ?? Currency::query()->findOrFail($payment->currency_code);
        $response = $this->apiRequest(
            'POST',
            '/v2/payments/captures/'.rawurlencode($payment->provider_capture_id).'/refund',
            [
                'amount' => [
                    'value' => $this->formatAmount($refund->amount_minor, $currency->minor_unit),
                    'currency_code' => $refund->currency_code,
                ],
            ],
            ['PayPal-Request-Id' => $refund->idempotency_key],
        );

        $providerRefundId = $response['id'] ?? null;

        if (! is_string($providerRefundId)) {
            throw new PaymentGatewayException('paypal_invalid_refund_response');
        }

        return new ProviderRefundResult(
            $providerRefundId,
            match ($response['status'] ?? null) {
                'COMPLETED' => PaymentRefundStatus::Succeeded,
                'PENDING' => PaymentRefundStatus::Processing,
                'CANCELLED' => PaymentRefundStatus::Cancelled,
                default => PaymentRefundStatus::Failed,
            },
        );
    }

    private function normalizeEvent(array $payload, string $payloadHash): VerifiedPaymentEvent
    {
        $resource = $payload['resource'];
        $eventType = $payload['event_type'];
        $relatedIds = $resource['supplementary_data']['related_ids'] ?? [];
        $kind = match ($eventType) {
            'CHECKOUT.ORDER.APPROVED' => PaymentEventKind::OrderApproved,
            'CHECKOUT.ORDER.VOIDED' => PaymentEventKind::OrderCancelled,
            'PAYMENT.CAPTURE.PENDING' => PaymentEventKind::CapturePending,
            'PAYMENT.CAPTURE.COMPLETED' => PaymentEventKind::CaptureCompleted,
            'PAYMENT.CAPTURE.DENIED' => PaymentEventKind::CaptureDenied,
            'PAYMENT.CAPTURE.REFUNDED' => PaymentEventKind::CaptureRefunded,
            'PAYMENT.CAPTURE.REVERSED', 'CHECKOUT.PAYMENT-APPROVAL.REVERSED' => PaymentEventKind::CaptureReversed,
            default => PaymentEventKind::Ignored,
        };
        $amount = $resource['amount'] ?? null;
        $currencyCode = is_array($amount) && is_string($amount['currency_code'] ?? null)
            ? $amount['currency_code']
            : null;
        $currency = $currencyCode === null ? null : Currency::query()->find($currencyCode);
        $amountMinor = is_array($amount) && $currency !== null
            ? $this->parseAmount($amount['value'] ?? null, $currency->minor_unit)
            : null;
        $isCaptureEvent = str_starts_with($eventType, 'PAYMENT.CAPTURE.')
            || $eventType === 'CHECKOUT.PAYMENT-APPROVAL.REVERSED';
        $isRefundEvent = $eventType === 'PAYMENT.CAPTURE.REFUNDED';

        $sanitizedPayload = [
            'event_type' => $eventType,
            'resource_id' => is_string($resource['id'] ?? null) ? $resource['id'] : null,
            'resource_status' => is_string($resource['status'] ?? null) ? $resource['status'] : null,
            'order_id' => is_string($relatedIds['order_id'] ?? null)
                ? $relatedIds['order_id']
                : (str_starts_with($eventType, 'CHECKOUT.ORDER.') && is_string($resource['id'] ?? null) ? $resource['id'] : null),
            'capture_id' => is_string($relatedIds['capture_id'] ?? null)
                ? $relatedIds['capture_id']
                : ($isCaptureEvent && ! $isRefundEvent && is_string($resource['id'] ?? null) ? $resource['id'] : null),
            'refund_id' => $isRefundEvent && is_string($resource['id'] ?? null) ? $resource['id'] : null,
            'amount_minor' => $amountMinor,
            'currency_code' => $currencyCode,
        ];

        return new VerifiedPaymentEvent(
            provider: $this->provider(),
            providerEventId: $payload['id'],
            eventType: $eventType,
            kind: $kind,
            providerOrderId: $sanitizedPayload['order_id'],
            providerCaptureId: $sanitizedPayload['capture_id'],
            providerRefundId: $sanitizedPayload['refund_id'],
            amountMinor: $amountMinor,
            currencyCode: $currencyCode,
            providerStatus: is_string($resource['status'] ?? null) ? $resource['status'] : null,
            payloadHash: $payloadHash,
            sanitizedPayload: $sanitizedPayload,
        );
    }

    private function apiRequest(string $method, string $path, array $payload = [], array $headers = []): array
    {
        $token = $this->accessToken();

        try {
            $response = Http::acceptJson()
                ->withToken($token)
                ->withHeaders($headers)
                ->timeout(20)
                ->send(
                    $method,
                    $this->baseUrl().$path,
                    $method === 'GET' || $payload === [] ? [] : ['json' => $payload],
                );
        } catch (ConnectionException $exception) {
            throw new PaymentGatewayException('paypal_connection_error', previous: $exception);
        }

        if (! $response->successful() || ! is_array($response->json())) {
            throw new PaymentGatewayException('paypal_api_error');
        }

        return $response->json();
    }

    private function accessToken(): string
    {
        $this->ensureConfigured();

        try {
            $response = Http::acceptJson()
                ->asForm()
                ->withBasicAuth(
                    (string) config('services.paypal.client_id'),
                    (string) config('services.paypal.client_secret'),
                )
                ->timeout(20)
                ->post($this->baseUrl().'/v1/oauth2/token', ['grant_type' => 'client_credentials']);
        } catch (ConnectionException $exception) {
            throw new PaymentGatewayException('paypal_connection_error', previous: $exception);
        }

        $token = $response->json('access_token');

        if (! $response->successful() || ! is_string($token) || $token === '') {
            throw new PaymentGatewayException('paypal_authentication_error');
        }

        return $token;
    }

    private function baseUrl(): string
    {
        return match (config('services.paypal.mode')) {
            'sandbox' => 'https://api-m.sandbox.paypal.com',
            'live' => 'https://api-m.paypal.com',
            default => throw new PaymentGatewayException('paypal_mode_invalid'),
        };
    }

    private function ensureConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new PaymentGatewayException('paypal_not_configured');
        }
    }

    private function formatAmount(int $amountMinor, int $minorUnit): string
    {
        if ($amountMinor < 0 || $minorUnit < 0 || $minorUnit > 4) {
            throw new PaymentGatewayException('invalid_payment_amount');
        }

        if ($minorUnit === 0) {
            return (string) $amountMinor;
        }

        $divisor = 10 ** $minorUnit;

        return intdiv($amountMinor, $divisor).'.'.str_pad((string) ($amountMinor % $divisor), $minorUnit, '0', STR_PAD_LEFT);
    }

    private function providerAmountMatches(mixed $amount, int $expectedMinor, string $expectedCurrency, int $minorUnit): bool
    {
        return is_array($amount)
            && ($amount['currency_code'] ?? null) === $expectedCurrency
            && $this->parseAmount($amount['value'] ?? null, $minorUnit) === $expectedMinor;
    }

    private function parseAmount(mixed $value, int $minorUnit): ?int
    {
        if (! is_string($value) || ! preg_match('/^(0|[1-9]\d*)(?:\.(\d+))?$/', $value, $matches)) {
            return null;
        }

        $fraction = $matches[2] ?? '';

        if (strlen($fraction) > $minorUnit) {
            return null;
        }

        $whole = (int) $matches[1];
        $factor = 10 ** $minorUnit;

        if ($whole > intdiv(PHP_INT_MAX, $factor)) {
            return null;
        }

        $minor = ($whole * $factor) + (int) str_pad($fraction, $minorUnit, '0');

        return $minor >= 0 ? $minor : null;
    }
}

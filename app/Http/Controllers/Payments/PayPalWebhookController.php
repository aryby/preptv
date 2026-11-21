<?php

namespace App\Http\Controllers\Payments;

use App\Domain\Billing\Exceptions\InvoiceProcessingException;
use App\Domain\Payments\Actions\ProcessPaymentWebhook;
use App\Domain\Payments\Actions\RecordRejectedWebhook;
use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Enums\PaymentWebhookDisposition;
use App\Domain\Payments\Exceptions\InvalidWebhookPayload;
use App\Domain\Payments\Exceptions\InvalidWebhookSignature;
use App\Domain\Payments\Exceptions\PaymentGatewayException;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayPalWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        PaymentGateway $paymentGateway,
        ProcessPaymentWebhook $processPaymentWebhook,
        RecordRejectedWebhook $recordRejectedWebhook,
    ) {
        $rawBody = $request->getContent();
        $transmissionId = (string) $request->header('PAYPAL-TRANSMISSION-ID', '');
        $headers = [
            'paypal-auth-algo' => $request->header('PAYPAL-AUTH-ALGO'),
            'paypal-cert-url' => $request->header('PAYPAL-CERT-URL'),
            'paypal-transmission-id' => $request->header('PAYPAL-TRANSMISSION-ID'),
            'paypal-transmission-sig' => $request->header('PAYPAL-TRANSMISSION-SIG'),
            'paypal-transmission-time' => $request->header('PAYPAL-TRANSMISSION-TIME'),
        ];

        try {
            $event = $paymentGateway->verifyWebhook($rawBody, $headers);
        } catch (InvalidWebhookSignature|InvalidWebhookPayload $exception) {
            $recordRejectedWebhook->execute(
                hash('sha256', $rawBody),
                $transmissionId,
                $exception instanceof InvalidWebhookSignature ? 'invalid_signature' : 'invalid_payload',
            );

            return response()->json(['status' => 'rejected'], 400);
        } catch (PaymentGatewayException $exception) {
            Log::warning('PayPal webhook verification is temporarily unavailable.', [
                'failure_code' => $exception->failureCode,
            ]);

            return response()->json(['status' => 'retry'], 503);
        }

        try {
            $result = $processPaymentWebhook->execute($event);
        } catch (PaymentGatewayException $exception) {
            Log::warning('PayPal webhook processing needs to be retried.', [
                'provider_event_id' => $event->providerEventId,
                'failure_code' => $exception->failureCode,
            ]);

            return response()->json(['status' => 'retry'], 503);
        } catch (InvoiceProcessingException $exception) {
            Log::warning('Invoice processing after a confirmed payment needs to be retried.', [
                'provider_event_id' => $event->providerEventId,
                'failure_code' => $exception->failureCode,
            ]);

            return response()->json(['status' => 'retry'], 503);
        }

        return response()->json(
            ['status' => $result->value],
            $result === PaymentWebhookDisposition::Retry ? 503 : 200,
        );
    }
}

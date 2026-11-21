<?php

namespace App\Http\Controllers\Payments;

use App\Domain\Orders\Services\OrderAccessService;
use App\Domain\Payments\Actions\CreatePaymentAttempt;
use App\Domain\Payments\Exceptions\PaymentGatewayException;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\RedirectResponse;

class StartPayPalCheckoutController extends Controller
{
    public function __invoke(
        OrderAccessService $orderAccessService,
        CreatePaymentAttempt $createPaymentAttempt,
        string $publicId,
        string $token,
    ): RedirectResponse {
        $order = $orderAccessService->findByAccessToken($publicId, $token);

        try {
            $checkout = $createPaymentAttempt->execute(
                $order,
                route('payments.paypal.return'),
                route('payments.paypal.cancel'),
            );
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (PaymentGatewayException $exception) {
            Log::warning('PayPal checkout could not be started.', [
                'order_public_id' => $order->public_id,
                'failure_code' => $exception->failureCode,
            ]);

            return redirect()->to(URL::temporarySignedRoute(
                'orders.show',
                $order->guest_access_expires_at,
                ['publicId' => $order->public_id, 'token' => $token],
            ))->withErrors(['payment' => 'Le paiement PayPal est temporairement indisponible. Réessayez plus tard.']);
        }

        return redirect()->away($checkout->approvalUrl);
    }
}

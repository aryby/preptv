<?php

namespace App\Domain\Payments\Actions;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Data\CheckoutSession;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Exceptions\PaymentGatewayException;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreatePaymentAttempt
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    public function execute(Order $order, string $returnUrl, string $cancelUrl): CheckoutSession
    {
        if (! $this->gateway->isConfigured()) {
            throw new PaymentGatewayException('paypal_not_configured');
        }

        $payment = DB::transaction(function () use ($order): Payment {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->getKey());

            if (
                $lockedOrder->status !== OrderStatus::PendingPayment
                || ($lockedOrder->expires_at !== null && $lockedOrder->expires_at->isPast())
            ) {
                throw ValidationException::withMessages([
                    'order' => 'Cette commande ne peut plus être payée.',
                ]);
            }

            if ($lockedOrder->total_minor < 1) {
                throw ValidationException::withMessages([
                    'order' => 'Le montant de cette commande est invalide.',
                ]);
            }

            if ($lockedOrder->payments()
                ->where('provider', $this->gateway->provider())
                ->whereIn('status', [PaymentStatus::Succeeded->value, PaymentStatus::PartiallyRefunded->value, PaymentStatus::Refunded->value])
                ->exists()) {
                throw ValidationException::withMessages([
                    'order' => 'Cette commande a déjà été payée.',
                ]);
            }

            if ($lockedOrder->payments()
                ->where('provider', $this->gateway->provider())
                ->where('status', PaymentStatus::Processing->value)
                ->lockForUpdate()
                ->exists()) {
                throw ValidationException::withMessages([
                    'order' => 'Un paiement pour cette commande est déjà en cours de vérification.',
                ]);
            }

            $existingAttempt = $lockedOrder->payments()
                ->where('provider', $this->gateway->provider())
                ->where('status', PaymentStatus::Pending->value)
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($existingAttempt !== null) {
                if (
                    $existingAttempt->amount_minor !== $lockedOrder->total_minor
                    || $existingAttempt->currency_code !== $lockedOrder->currency_code
                ) {
                    throw new PaymentGatewayException('payment_snapshot_mismatch');
                }

                return $existingAttempt;
            }

            return Model::unguarded(fn (): Payment => Payment::query()->create([
                'order_id' => $lockedOrder->getKey(),
                'provider' => $this->gateway->provider(),
                'idempotency_key' => (string) Str::uuid(),
                'status' => PaymentStatus::Pending,
                'amount_minor' => $lockedOrder->total_minor,
                'currency_code' => $lockedOrder->currency_code,
            ]));
        });

        try {
            $checkout = $this->gateway->createCheckout(
                $payment,
                $order->loadMissing('currency'),
                $returnUrl,
                $cancelUrl,
            );
        } catch (PaymentGatewayException $exception) {
            $payment->forceFill([
                'failure_code' => $exception->failureCode,
            ])->save();

            throw $exception;
        }

        DB::transaction(function () use ($payment, $checkout): void {
            $payment->newQuery()
                ->whereKey($payment->getKey())
                ->lockForUpdate()
                ->firstOrFail()
                ->forceFill(['provider_payment_id' => $checkout->providerPaymentId])
                ->save();
        });

        return $checkout;
    }
}

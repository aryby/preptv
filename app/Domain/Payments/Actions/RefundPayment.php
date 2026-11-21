<?php

namespace App\Domain\Payments\Actions;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Enums\PaymentRefundStatus;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Exceptions\PaymentGatewayException;
use App\Models\Payment;
use App\Models\PaymentRefund;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefundPayment
{
    public function __construct(private readonly PaymentGateway $gateway) {}

    public function execute(
        Payment $payment,
        int $amountMinor,
        string $currencyCode,
        string $idempotencyKey,
    ): PaymentRefund {
        $refund = DB::transaction(function () use ($payment, $amountMinor, $currencyCode, $idempotencyKey): PaymentRefund {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());
            $existing = PaymentRefund::query()
                ->where('payment_id', $lockedPayment->getKey())
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                if ($existing->amount_minor !== $amountMinor || $existing->currency_code !== $currencyCode) {
                    throw ValidationException::withMessages([
                        'idempotencyKey' => 'Cette clé d’idempotence est déjà utilisée pour un remboursement différent.',
                    ]);
                }

                return $existing;
            }

            if (
                ! in_array($lockedPayment->status, [PaymentStatus::Succeeded, PaymentStatus::PartiallyRefunded], true)
                || $lockedPayment->provider_capture_id === null
                || $amountMinor < 1
                || $currencyCode !== $lockedPayment->currency_code
                || ! preg_match('/^[A-Za-z0-9._:-]{1,38}$/D', $idempotencyKey)
            ) {
                throw ValidationException::withMessages([
                    'payment' => 'Ce paiement ne peut pas être remboursé avec les données fournies.',
                ]);
            }

            $reservedMinor = (int) PaymentRefund::query()
                ->where('payment_id', $lockedPayment->getKey())
                ->whereIn('status', [
                    PaymentRefundStatus::Pending->value,
                    PaymentRefundStatus::Processing->value,
                    PaymentRefundStatus::Succeeded->value,
                ])
                ->sum('amount_minor');

            if ($amountMinor > $lockedPayment->amount_minor - $reservedMinor) {
                throw ValidationException::withMessages([
                    'amountMinor' => 'Le montant dépasse le solde remboursable.',
                ]);
            }

            return Model::unguarded(fn (): PaymentRefund => PaymentRefund::query()->create([
                'payment_id' => $lockedPayment->getKey(),
                'idempotency_key' => $idempotencyKey,
                'status' => PaymentRefundStatus::Pending,
                'amount_minor' => $amountMinor,
                'currency_code' => $currencyCode,
            ]));
        });

        if ($refund->provider_refund_id !== null && $refund->status !== PaymentRefundStatus::Failed) {
            return $refund;
        }

        try {
            $result = $this->gateway->refund($payment->loadMissing('currency'), $refund);
        } catch (PaymentGatewayException $exception) {
            $refund->forceFill([
                'status' => PaymentRefundStatus::Processing,
                'reason_code' => $exception->failureCode,
            ])->save();

            throw $exception;
        }

        return DB::transaction(function () use ($refund, $result): PaymentRefund {
            $lockedRefund = PaymentRefund::query()->lockForUpdate()->findOrFail($refund->getKey());
            $lockedRefund->forceFill([
                'provider_refund_id' => $result->providerRefundId,
                'status' => $result->status,
                'completed_at' => $result->status === PaymentRefundStatus::Succeeded ? now() : null,
                'reason_code' => $result->status === PaymentRefundStatus::Failed ? 'paypal_refund_failed' : null,
            ])->save();

            if ($result->status === PaymentRefundStatus::Succeeded) {
                $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($lockedRefund->payment_id);
                $refundedMinor = (int) PaymentRefund::query()
                    ->where('payment_id', $lockedPayment->getKey())
                    ->where('status', PaymentRefundStatus::Succeeded->value)
                    ->sum('amount_minor');
                $lockedPayment->forceFill([
                    'status' => $refundedMinor >= $lockedPayment->amount_minor
                        ? PaymentStatus::Refunded
                        : PaymentStatus::PartiallyRefunded,
                ])->save();
                $lockedPayment->order()
                    ->lockForUpdate()
                    ->firstOrFail()
                    ->forceFill([
                        'status' => $refundedMinor >= $lockedPayment->amount_minor
                            ? OrderStatus::Refunded
                            : OrderStatus::PartiallyRefunded,
                    ])
                    ->save();
            }

            return $lockedRefund;
        });
    }
}

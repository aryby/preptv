<?php

namespace App\Domain\Payments\Actions;

use App\Domain\Billing\Actions\IssueInvoiceForPaidOrder;
use App\Domain\Billing\Actions\SendInvoiceEmail;
use App\Domain\Billing\Exceptions\InvoiceProcessingException;
use App\Domain\Billing\Services\GenerateInvoicePdf;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Data\VerifiedPaymentEvent;
use App\Domain\Payments\Enums\PaymentEventKind;
use App\Domain\Payments\Enums\PaymentRefundStatus;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Enums\PaymentWebhookDisposition;
use App\Domain\Payments\Enums\PaymentWebhookEventStatus;
use App\Domain\Payments\Exceptions\PaymentEventRejected;
use App\Domain\Payments\Exceptions\PaymentGatewayException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Models\PaymentWebhookEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessPaymentWebhook
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly IssueInvoiceForPaidOrder $issueInvoiceForPaidOrder,
        private readonly GenerateInvoicePdf $generateInvoicePdf,
        private readonly SendInvoiceEmail $sendInvoiceEmail,
    ) {}

    public function execute(VerifiedPaymentEvent $event): PaymentWebhookDisposition
    {
        $processing = $this->beginProcessing($event);

        if ($processing instanceof PaymentWebhookDisposition) {
            return $processing;
        }

        try {
            $payment = $this->applyEvent($event);
        } catch (PaymentEventRejected $exception) {
            $this->finishEvent($processing, PaymentWebhookEventStatus::Rejected, $exception->failureCode);

            return PaymentWebhookDisposition::Rejected;
        } catch (PaymentGatewayException $exception) {
            $this->finishEvent($processing, PaymentWebhookEventStatus::Failed, $exception->failureCode);

            throw $exception;
        } catch (InvoiceProcessingException $exception) {
            $this->finishEvent($processing, PaymentWebhookEventStatus::Failed, $exception->failureCode);

            throw $exception;
        }

        $this->finishEvent($processing, PaymentWebhookEventStatus::Processed, null, $payment?->getKey());

        return PaymentWebhookDisposition::Processed;
    }

    private function beginProcessing(VerifiedPaymentEvent $event): PaymentWebhookEvent|PaymentWebhookDisposition
    {
        return DB::transaction(function () use ($event): PaymentWebhookEvent|PaymentWebhookDisposition {
            $now = now();

            DB::table((new PaymentWebhookEvent)->getTable())->insertOrIgnore([
                'provider' => $event->provider,
                'provider_event_id' => $event->providerEventId,
                'payment_id' => null,
                'payload_hash' => $event->payloadHash,
                'sanitized_payload' => $event->sanitizedPayload === null
                    ? null
                    : json_encode($event->sanitizedPayload, JSON_THROW_ON_ERROR),
                'status' => PaymentWebhookEventStatus::Received->value,
                'attempt_count' => 0,
                'received_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $eventRow = PaymentWebhookEvent::query()
                ->where('provider', $event->provider)
                ->where('provider_event_id', $event->providerEventId)
                ->lockForUpdate()
                ->firstOrFail();

            if (! hash_equals($eventRow->payload_hash, $event->payloadHash)) {
                Log::warning('PayPal webhook event ID was replayed with a different payload.', [
                    'provider_event_id' => $event->providerEventId,
                    'stored_payload_hash' => $eventRow->payload_hash,
                    'received_payload_hash' => $event->payloadHash,
                ]);

                return PaymentWebhookDisposition::Rejected;
            }

            if (
                in_array($eventRow->status, [PaymentWebhookEventStatus::Processed, PaymentWebhookEventStatus::Rejected], true)
                || ($eventRow->status === PaymentWebhookEventStatus::Processing && $eventRow->updated_at?->greaterThan(now()->subMinute()))
            ) {
                $eventRow->increment('attempt_count');

                return $eventRow->status === PaymentWebhookEventStatus::Processing
                    ? PaymentWebhookDisposition::Retry
                    : PaymentWebhookDisposition::Duplicate;
            }

            $eventRow->forceFill([
                'status' => PaymentWebhookEventStatus::Processing,
                'attempt_count' => $eventRow->attempt_count + 1,
                'failure_code' => null,
            ])->save();

            return $eventRow;
        });
    }

    private function applyEvent(VerifiedPaymentEvent $event): ?Payment
    {
        if ($event->kind === PaymentEventKind::Ignored) {
            return null;
        }

        $this->assertProviderStatus($event);
        $payment = $this->findPayment($event);

        if ($event->kind === PaymentEventKind::CaptureRefunded) {
            $this->applyRefundEvent($payment, $event);

            return $payment;
        }

        if ($event->kind === PaymentEventKind::OrderCancelled) {
            DB::transaction(function () use ($payment): void {
                $payment = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());

                if (! in_array($payment->status, [PaymentStatus::Succeeded, PaymentStatus::Refunded, PaymentStatus::PartiallyRefunded], true)) {
                    $payment->forceFill(['status' => PaymentStatus::Cancelled])->save();
                }
            });

            return $payment;
        }

        if ($event->kind === PaymentEventKind::OrderApproved) {
            if ($event->providerOrderId === null || $payment->provider_payment_id !== $event->providerOrderId) {
                throw new PaymentEventRejected('paypal_order_mismatch');
            }

            if ($payment->order->status !== OrderStatus::PendingPayment) {
                throw new PaymentEventRejected('order_not_pending');
            }

            DB::transaction(function () use ($payment): void {
                $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());

                if (! in_array($lockedPayment->status, [PaymentStatus::Pending, PaymentStatus::Processing], true)) {
                    throw new PaymentEventRejected('payment_not_pending');
                }

                $lockedPayment->forceFill(['status' => PaymentStatus::Processing])->save();
            });

            $captureId = $this->gateway->captureApprovedPayment($payment->loadMissing(['order', 'currency']), $event->providerOrderId);

            if ($captureId !== null) {
                $payment->forceFill(['provider_capture_id' => $captureId])->save();
            }

            return $payment;
        }

        $this->assertFullPaymentAmount($payment, $event);

        $invoice = null;

        DB::transaction(function () use ($payment, $event, &$invoice): void {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());

            if ($event->providerCaptureId !== null && $lockedPayment->provider_capture_id === null) {
                $lockedPayment->provider_capture_id = $event->providerCaptureId;
            }

            $duplicateOrderPayment = $event->kind === PaymentEventKind::CaptureCompleted
                && Payment::query()
                    ->where('order_id', $lockedPayment->order_id)
                    ->where('id', '!=', $lockedPayment->getKey())
                    ->whereIn('status', [
                        PaymentStatus::Succeeded->value,
                        PaymentStatus::PartiallyRefunded->value,
                        PaymentStatus::Refunded->value,
                    ])
                    ->exists();

            match ($event->kind) {
                PaymentEventKind::CapturePending => $this->markProcessing($lockedPayment),
                PaymentEventKind::CaptureCompleted => $this->markSucceeded($lockedPayment),
                PaymentEventKind::CaptureDenied => $this->markFailed($lockedPayment),
                PaymentEventKind::CaptureReversed => $this->markReversed($lockedPayment),
                default => null,
            };

            if ($duplicateOrderPayment) {
                $lockedPayment->failure_code = 'duplicate_order_payment';
            }

            $lockedPayment->save();

            if ($event->kind === PaymentEventKind::CaptureCompleted) {
                $orderStatus = match ($lockedPayment->status) {
                    PaymentStatus::PartiallyRefunded => OrderStatus::PartiallyRefunded,
                    PaymentStatus::Refunded => OrderStatus::Refunded,
                    PaymentStatus::Reversed => OrderStatus::Reversed,
                    default => OrderStatus::Paid,
                };

                Order::query()
                    ->whereKey($lockedPayment->order_id)
                    ->lockForUpdate()
                    ->firstOrFail()
                    ->forceFill(['status' => $orderStatus])
                    ->save();

                if (! $duplicateOrderPayment && $lockedPayment->status === PaymentStatus::Succeeded) {
                    $invoice = $this->issueInvoiceForPaidOrder->execute($lockedPayment);
                }
            }

            if ($event->kind === PaymentEventKind::CaptureReversed) {
                Order::query()
                    ->whereKey($lockedPayment->order_id)
                    ->lockForUpdate()
                    ->firstOrFail()
                    ->forceFill(['status' => OrderStatus::Reversed])
                    ->save();
            }
        });

        if ($invoice !== null) {
            $this->generateInvoicePdf->execute($invoice);
            $this->sendInvoiceEmail->execute($invoice);
        }

        return $payment;
    }

    private function assertProviderStatus(VerifiedPaymentEvent $event): void
    {
        $expectedStatus = match ($event->kind) {
            PaymentEventKind::OrderApproved => 'APPROVED',
            PaymentEventKind::OrderCancelled => 'VOIDED',
            PaymentEventKind::CapturePending => 'PENDING',
            PaymentEventKind::CaptureCompleted, PaymentEventKind::CaptureRefunded => 'COMPLETED',
            PaymentEventKind::CaptureDenied => 'DENIED',
            PaymentEventKind::CaptureReversed => 'REVERSED',
            PaymentEventKind::Ignored => null,
        };

        if ($event->providerStatus !== $expectedStatus) {
            throw new PaymentEventRejected('provider_status_mismatch');
        }
    }

    private function findPayment(VerifiedPaymentEvent $event): Payment
    {
        if ($event->providerOrderId === null && $event->providerCaptureId === null) {
            throw new PaymentEventRejected('payment_reference_missing');
        }

        $payment = Payment::query()
            ->with(['order', 'currency'])
            ->where('provider', $event->provider)
            ->where(function ($query) use ($event): void {
                if ($event->providerOrderId !== null) {
                    $query->where('provider_payment_id', $event->providerOrderId);
                }

                if ($event->providerCaptureId !== null) {
                    $event->providerOrderId === null
                        ? $query->where('provider_capture_id', $event->providerCaptureId)
                        : $query->orWhere('provider_capture_id', $event->providerCaptureId);
                }
            })
            ->first();

        if ($payment === null) {
            throw new PaymentEventRejected('payment_not_found');
        }

        return $payment;
    }

    private function assertFullPaymentAmount(Payment $payment, VerifiedPaymentEvent $event): void
    {
        if (
            $event->amountMinor === null
            || $event->currencyCode === null
            || $event->amountMinor !== $payment->amount_minor
            || $event->currencyCode !== $payment->currency_code
            || $payment->order->total_minor !== $payment->amount_minor
            || $payment->order->currency_code !== $payment->currency_code
            || ($event->providerOrderId !== null && $event->providerOrderId !== $payment->provider_payment_id)
            || ($event->providerCaptureId !== null && $payment->provider_capture_id !== null && $event->providerCaptureId !== $payment->provider_capture_id)
        ) {
            throw new PaymentEventRejected('payment_amount_or_currency_mismatch');
        }
    }

    private function applyRefundEvent(Payment $payment, VerifiedPaymentEvent $event): void
    {
        if (
            $event->providerRefundId === null
            || $event->amountMinor === null
            || $event->amountMinor < 1
            || $event->currencyCode !== $payment->currency_code
            || ($event->providerCaptureId !== null && $event->providerCaptureId !== $payment->provider_capture_id)
            || ($event->providerOrderId !== null && $event->providerOrderId !== $payment->provider_payment_id)
        ) {
            throw new PaymentEventRejected('refund_amount_or_reference_mismatch');
        }

        DB::transaction(function () use ($payment, $event): void {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());
            $refund = PaymentRefund::query()
                ->where('payment_id', $lockedPayment->getKey())
                ->where('provider_refund_id', $event->providerRefundId)
                ->lockForUpdate()
                ->first();

            if ($refund === null || $refund->amount_minor !== $event->amountMinor || $refund->currency_code !== $event->currencyCode) {
                throw new PaymentEventRejected('refund_not_reconciled');
            }

            $refund->forceFill([
                'status' => PaymentRefundStatus::Succeeded,
                'completed_at' => now(),
                'reason_code' => null,
            ])->save();

            $refundedMinor = (int) PaymentRefund::query()
                ->where('payment_id', $lockedPayment->getKey())
                ->where('status', PaymentRefundStatus::Succeeded->value)
                ->sum('amount_minor');

            $lockedPayment->forceFill([
                'status' => $refundedMinor >= $lockedPayment->amount_minor
                    ? PaymentStatus::Refunded
                    : PaymentStatus::PartiallyRefunded,
            ])->save();

            $orderStatus = $refundedMinor >= $lockedPayment->amount_minor
                ? OrderStatus::Refunded
                : OrderStatus::PartiallyRefunded;

            Order::query()
                ->whereKey($lockedPayment->order_id)
                ->lockForUpdate()
                ->firstOrFail()
                ->forceFill(['status' => $orderStatus])
                ->save();
        });
    }

    private function markProcessing(Payment $payment): void
    {
        if (in_array($payment->status, [PaymentStatus::Pending, PaymentStatus::Processing], true)) {
            $payment->status = PaymentStatus::Processing;
        }
    }

    private function markSucceeded(Payment $payment): void
    {
        if (in_array($payment->status, [PaymentStatus::PartiallyRefunded, PaymentStatus::Refunded, PaymentStatus::Reversed], true)) {
            return;
        }

        $payment->status = PaymentStatus::Succeeded;
        $payment->confirmed_at = now();
        $payment->failure_code = null;
    }

    private function markFailed(Payment $payment): void
    {
        if (! in_array($payment->status, [PaymentStatus::Succeeded, PaymentStatus::Refunded, PaymentStatus::PartiallyRefunded], true)) {
            $payment->status = PaymentStatus::Failed;
            $payment->failure_code = 'paypal_capture_denied';
        }
    }

    private function markReversed(Payment $payment): void
    {
        $payment->status = PaymentStatus::Reversed;
        $payment->failure_code = 'paypal_capture_reversed';
    }

    private function finishEvent(
        PaymentWebhookEvent $eventRow,
        PaymentWebhookEventStatus $status,
        ?string $failureCode,
        ?int $paymentId = null,
    ): void {
        $eventRow->forceFill([
            'payment_id' => $paymentId,
            'status' => $status,
            'failure_code' => $failureCode,
            'processed_at' => now(),
        ])->save();
    }
}

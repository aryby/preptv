<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Enums\InvoiceStatus;
use App\Domain\Billing\Exceptions\InvoiceProcessingException;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IssueInvoiceForPaidOrder
{
    public function execute(Payment $payment): Invoice
    {
        return DB::transaction(function () use ($payment): Invoice {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());
            $order = Order::query()->lockForUpdate()->findOrFail($lockedPayment->order_id);

            if (
                $lockedPayment->status !== PaymentStatus::Succeeded
                || $lockedPayment->confirmed_at === null
                || $order->status !== OrderStatus::Paid
                || $lockedPayment->amount_minor !== $order->total_minor
                || $lockedPayment->currency_code !== $order->currency_code
            ) {
                throw new InvoiceProcessingException('invoice_requires_confirmed_payment');
            }

            $existingInvoice = Invoice::query()
                ->where('order_id', $order->getKey())
                ->lockForUpdate()
                ->first();

            if ($existingInvoice !== null) {
                if (
                    $existingInvoice->currency_code !== $order->currency_code
                    || $existingInvoice->total_minor !== $order->total_minor
                ) {
                    throw new InvoiceProcessingException('existing_invoice_snapshot_mismatch');
                }

                return $existingInvoice->load(['items', 'currency']);
            }

            $buyerSnapshot = $order->customer_snapshot;

            if (
                ! is_array($buyerSnapshot)
                || ! is_string($buyerSnapshot['name'] ?? null)
                || trim($buyerSnapshot['name']) === ''
                || ! is_string($buyerSnapshot['email'] ?? null)
                || filter_var($buyerSnapshot['email'], FILTER_VALIDATE_EMAIL) === false
            ) {
                throw new InvoiceProcessingException('invoice_buyer_snapshot_incomplete');
            }

            $sellerSnapshot = $this->sellerSnapshot($order->seller_snapshot);
            $orderItems = OrderItem::query()
                ->where('order_id', $order->getKey())
                ->orderBy('line_number')
                ->lockForUpdate()
                ->get();

            if ($orderItems->isEmpty()) {
                throw new InvoiceProcessingException('invoice_order_items_missing');
            }

            foreach ($orderItems as $item) {
                if (
                    $item->currency_code !== $order->currency_code
                    || $item->quantity < 1
                    || $item->unit_amount_minor < 0
                    || $item->subtotal_minor < 0
                    || $item->tax_minor < 0
                    || $item->subtotal_minor > PHP_INT_MAX - $item->tax_minor
                    || $item->total_minor !== $item->subtotal_minor + $item->tax_minor
                ) {
                    throw new InvoiceProcessingException('invoice_order_item_snapshot_invalid');
                }
            }

            if (
                $this->sum($orderItems->pluck('subtotal_minor')->all()) !== $order->subtotal_minor
                || $this->sum($orderItems->pluck('tax_minor')->all()) !== $order->tax_minor
                || $this->sum($orderItems->pluck('total_minor')->all()) !== $order->total_minor
            ) {
                throw new InvoiceProcessingException('invoice_order_total_mismatch');
            }

            $issuedAt = now();
            $invoice = Model::unguarded(fn (): Invoice => Invoice::query()->create([
                'invoice_number' => 'DRAFT-'.Str::ulid(),
                'order_id' => $order->getKey(),
                'subscription_id' => null,
                'user_id' => $order->user_id,
                'reseller_profile_id' => $order->reseller_profile_id,
                'currency_code' => $order->currency_code,
                'status' => InvoiceStatus::Draft,
                'subtotal_minor' => $order->subtotal_minor,
                'tax_minor' => $order->tax_minor,
                'total_minor' => $order->total_minor,
                'buyer_snapshot' => $buyerSnapshot,
                'seller_snapshot' => $sellerSnapshot,
                'issued_at' => $issuedAt,
                'paid_at' => $lockedPayment->confirmed_at,
            ]));

            foreach ($orderItems as $item) {
                $description = $item->plan_name_snapshot.' — '.$item->billing_period_unit->label($item->billing_period_count);

                if (mb_strlen($description) > 255) {
                    throw new InvoiceProcessingException('invoice_item_description_too_long');
                }

                Model::unguarded(fn (): InvoiceItem => InvoiceItem::query()->create([
                    'invoice_id' => $invoice->getKey(),
                    'line_number' => $item->line_number,
                    'description_snapshot' => $description,
                    'quantity' => $item->quantity,
                    'unit_amount_minor' => $item->unit_amount_minor,
                    'subtotal_minor' => $item->subtotal_minor,
                    'tax_rate_basis_points' => $item->tax_rate_basis_points,
                    'tax_minor' => $item->tax_minor,
                    'tax_label_snapshot' => $item->tax_label_snapshot,
                    'total_minor' => $item->total_minor,
                    'currency_code' => $item->currency_code,
                ]));
            }

            $invoice->forceFill([
                'invoice_number' => $this->invoiceNumber($invoice->getKey(), $issuedAt),
                'status' => InvoiceStatus::Paid,
            ])->save();

            return $invoice->load(['items', 'currency']);
        });
    }

    /**
     * @param  array<string, mixed>|null  $orderSellerSnapshot
     * @return array<string, string>
     */
    private function sellerSnapshot(?array $orderSellerSnapshot): array
    {
        $seller = array_merge([
            'name' => config('billing.seller.name'),
            'address' => config('billing.seller.address'),
            'email' => config('billing.seller.email'),
            'tax_identifier' => config('billing.seller.tax_identifier'),
            'registration_number' => config('billing.seller.registration_number'),
            'country_code' => config('billing.seller.country_code'),
        ], $orderSellerSnapshot ?? []);
        $seller = array_filter($seller, fn (mixed $value): bool => is_string($value) && trim($value) !== '');

        foreach (['name', 'address'] as $requiredField) {
            if (! is_string($seller[$requiredField] ?? null)) {
                throw new InvoiceProcessingException('invoice_seller_configuration_incomplete');
            }
        }

        return array_map(static fn (string $value): string => trim($value), $seller);
    }

    /**
     * @param  array<int, int>  $amounts
     */
    private function sum(array $amounts): int
    {
        $total = 0;

        foreach ($amounts as $amount) {
            if ($amount < 0 || $amount > PHP_INT_MAX - $total) {
                throw new InvoiceProcessingException('invoice_amount_overflow');
            }

            $total += $amount;
        }

        return $total;
    }

    private function invoiceNumber(int $invoiceId, \DateTimeInterface $issuedAt): string
    {
        $prefix = config('billing.invoice.number_prefix');
        $format = config('billing.invoice.number_format');
        $padding = filter_var(config('billing.invoice.sequence_padding'), FILTER_VALIDATE_INT);

        if (
            ! is_string($prefix)
            || ! preg_match('/^[A-Za-z0-9._-]{1,20}$/D', $prefix)
            || ! is_string($format)
            || $padding === false
            || $padding < 1
            || $padding > 12
        ) {
            throw new InvoiceProcessingException('invoice_number_configuration_invalid');
        }

        $number = strtr($format, [
            '{prefix}' => $prefix,
            '{year}' => $issuedAt->format('Y'),
            '{sequence}' => str_pad((string) $invoiceId, $padding, '0', STR_PAD_LEFT),
        ]);

        if (! preg_match('/^[A-Za-z0-9._-]{1,64}$/D', $number)) {
            throw new InvoiceProcessingException('invoice_number_format_invalid');
        }

        return $number;
    }
}

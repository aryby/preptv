<?php

namespace App\Filament\Widgets;

use App\Domain\Payments\Enums\PaymentRefundStatus;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Models\Currency;
use App\Models\Payment;
use App\Models\PaymentRefund;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RevenueOverviewWidget extends StatsOverviewWidget
{
    use HasWidgetShield;

    protected ?string $heading = 'Chiffre d’affaires net';

    protected function getStats(): array
    {
        $grossByCurrency = Payment::query()
            ->whereIn('status', [
                PaymentStatus::Succeeded->value,
                PaymentStatus::PartiallyRefunded->value,
                PaymentStatus::Refunded->value,
            ])
            ->selectRaw('currency_code, SUM(amount_minor) as amount_minor')
            ->groupBy('currency_code')
            ->pluck('amount_minor', 'currency_code');

        $refundsByCurrency = PaymentRefund::query()
            ->join('payments', 'payments.id', '=', 'payment_refunds.payment_id')
            ->where('payment_refunds.status', PaymentRefundStatus::Succeeded->value)
            ->whereIn('payments.status', [
                PaymentStatus::Succeeded->value,
                PaymentStatus::PartiallyRefunded->value,
                PaymentStatus::Refunded->value,
            ])
            ->selectRaw('payments.currency_code as currency_code, SUM(payment_refunds.amount_minor) as amount_minor')
            ->groupBy('payments.currency_code')
            ->pluck('amount_minor', 'currency_code');

        $currencyCodes = $grossByCurrency->keys()
            ->merge($refundsByCurrency->keys())
            ->unique()
            ->values();

        if ($currencyCodes->isEmpty()) {
            return [
                Stat::make('Chiffre d’affaires net', 'Aucun paiement encaissé'),
            ];
        }

        $currencies = Currency::query()
            ->whereIn('code', $currencyCodes)
            ->get()
            ->keyBy('code');

        return $currencyCodes
            ->map(function (string $currencyCode) use ($currencies, $grossByCurrency, $refundsByCurrency): Stat {
                $currency = $currencies->get($currencyCode);
                abort_if($currency === null, 500, 'A payment references an unknown currency.');

                $amountMinor = (int) $grossByCurrency->get($currencyCode, 0)
                    - (int) $refundsByCurrency->get($currencyCode, 0);
                $scale = $currency->minor_unit;
                $amount = number_format($amountMinor / (10 ** $scale), $scale, '.', ',');

                return Stat::make("Net {$currencyCode}", "{$amount} {$currencyCode}")
                    ->description('Paiements encaissés moins remboursements confirmés')
                    ->color('success');
            })
            ->all();
    }
}

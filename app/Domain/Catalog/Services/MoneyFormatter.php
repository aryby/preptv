<?php

namespace App\Domain\Catalog\Services;

use App\Models\Currency;
use InvalidArgumentException;

class MoneyFormatter
{
    public function format(int $amountMinor, Currency $currency): string
    {
        if ($amountMinor < 0 || $currency->minor_unit < 0 || $currency->minor_unit > 4) {
            throw new InvalidArgumentException('Money values and currency scale are invalid.');
        }

        $scale = $currency->minor_unit;
        $divisor = [1, 10, 100, 1000, 10000][$scale];
        $whole = intdiv($amountMinor, $divisor);
        $fraction = $amountMinor % $divisor;
        $formatted = number_format($whole, 0, '.', ',');

        if ($scale > 0) {
            $formatted .= '.'.str_pad((string) $fraction, $scale, '0', STR_PAD_LEFT);
        }

        return "{$formatted} {$currency->code}";
    }
}

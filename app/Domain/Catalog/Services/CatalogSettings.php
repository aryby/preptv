<?php

namespace App\Domain\Catalog\Services;

use App\Models\Currency;
use App\Models\Setting;

class CatalogSettings
{
    public const DEFAULT_CURRENCY_KEY = 'catalog.default_currency';

    public function defaultCurrencyCode(): ?string
    {
        $setting = Setting::query()
            ->where('key', self::DEFAULT_CURRENCY_KEY)
            ->first();

        $currencyCode = $setting?->value['currency_code'] ?? null;

        return is_string($currencyCode) && $currencyCode !== ''
            ? $currencyCode
            : null;
    }

    public function defaultCurrency(): ?Currency
    {
        $currencyCode = $this->defaultCurrencyCode();

        if ($currencyCode === null) {
            return null;
        }

        return Currency::query()
            ->active()
            ->where('code', $currencyCode)
            ->first();
    }

    public function isDefault(Currency $currency): bool
    {
        return $this->defaultCurrencyCode() === $currency->code;
    }
}

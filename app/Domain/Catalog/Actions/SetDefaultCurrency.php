<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Services\CatalogSettings;
use App\Models\Currency;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SetDefaultCurrency
{
    public function execute(User $actor, string $currencyCode): Currency
    {
        $targetCurrency = Currency::query()
            ->where('code', $currencyCode)
            ->firstOrFail();
        Gate::forUser($actor)->authorize('update', $targetCurrency);

        return DB::transaction(function () use ($currencyCode): Currency {
            $currency = Currency::query()
                ->where('code', $currencyCode)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $currency->is_active) {
                throw ValidationException::withMessages([
                    'currency' => 'La devise par défaut doit être active.',
                ]);
            }

            $now = now();
            DB::table('settings')->insertOrIgnore([
                'key' => CatalogSettings::DEFAULT_CURRENCY_KEY,
                'category' => 'catalog',
                'value' => json_encode(['currency_code' => $currency->code], JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $setting = Setting::query()
                ->where('key', CatalogSettings::DEFAULT_CURRENCY_KEY)
                ->lockForUpdate()
                ->firstOrFail();
            $setting->value = ['currency_code' => $currency->code];
            $setting->category = 'catalog';
            $setting->save();

            return $currency;
        });
    }
}

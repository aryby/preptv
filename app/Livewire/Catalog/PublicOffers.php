<?php

namespace App\Livewire\Catalog;

use App\Domain\Catalog\Services\CatalogSettings;
use App\Domain\Catalog\Services\MoneyFormatter;
use App\Models\Currency;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class PublicOffers extends Component
{
    public string $currencyCode = '';

    public function mount(CatalogSettings $catalogSettings): void
    {
        $this->currencyCode = $catalogSettings->defaultCurrency()?->code ?? '';
    }

    protected function rules(): array
    {
        return [
            'currencyCode' => [
                'required',
                'string',
                Rule::exists('currencies', 'code')->where('is_active', true),
            ],
        ];
    }

    public function updatedCurrencyCode(): void
    {
        $this->validateOnly('currencyCode');
    }

    public function render(): View
    {
        $moneyFormatter = app(MoneyFormatter::class);
        $currencies = Currency::query()
            ->active()
            ->orderBy('name')
            ->get();
        $currency = $currencies->firstWhere('code', $this->currencyCode);

        if ($this->currencyCode !== '' && $currency === null) {
            $this->addError('currencyCode', 'La devise sélectionnée n’est pas active.');
        }

        $now = now();
        $pricesForCurrency = function (Builder|HasMany $query) use ($currency, $now): void {
            $query->effectiveAt($now);
            $query->where('currency_code', $currency?->code);
            $query->whereHas('currency', function (Builder $currencyQuery): void {
                $currencyQuery->active();
            });
            $query->with('currency');
        };

        $plans = Plan::query()
            ->published()
            ->when(
                $currency !== null,
                fn (Builder $query): Builder => $query->whereHas('planPrices', $pricesForCurrency),
                fn (Builder $query): Builder => $query->whereRaw('1 = 0'),
            )
            ->with(['planPrices' => $pricesForCurrency])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('livewire.catalog.public-offers', [
            'currencies' => $currencies,
            'currency' => $currency,
            'moneyFormatter' => $moneyFormatter,
            'plans' => $plans,
        ]);
    }
}

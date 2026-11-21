<?php

namespace App\Livewire\Catalog;

use App\Domain\Catalog\Services\CatalogSettings;
use App\Domain\Catalog\Services\MoneyFormatter;
use App\Domain\Orders\Actions\CreateOrderFromPlanPrice;
use App\Domain\Orders\Notifications\OrderConfirmationNotification;
use App\Models\Currency;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class PublicOffers extends Component
{
    public string $currencyCode = '';

    public string $selectedPlanPriceId = '';

    public string $billingName = '';

    public string $billingEmail = '';

    public string $orderUrl = '';

    public string $orderReference = '';

    public bool $emailDeliveryFailed = false;

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
        $this->selectedPlanPriceId = '';
        $this->resetErrorBag();
        $this->validateOnly('currencyCode');
    }

    public function choosePrice(mixed $priceId): void
    {
        $this->resetErrorBag();

        $validatedPriceId = Validator::make(
            ['priceId' => $priceId],
            ['priceId' => ['required', 'integer', 'min:1']],
        )->validate()['priceId'];

        if ($this->currencyCode === '') {
            $this->addError('currencyCode', 'Choisissez une devise active avant de sélectionner une offre.');

            return;
        }

        $isAvailable = PlanPrice::query()
            ->effectiveAt(now())
            ->whereKey((int) $validatedPriceId)
            ->where('currency_code', $this->currencyCode)
            ->whereHas('plan', function (Builder $query): void {
                $query->published();
            })
            ->whereHas('currency', function (Builder $query): void {
                $query->active();
            })
            ->exists();

        if (! $isAvailable) {
            $this->addError('selectedPlanPriceId', 'Cette durée n’est plus disponible.');

            return;
        }

        $this->selectedPlanPriceId = (string) $validatedPriceId;
        $this->emailDeliveryFailed = false;
        $this->orderUrl = '';
        $this->orderReference = '';
    }

    public function placeOrder(): void
    {
        if ($this->orderUrl !== '') {
            return;
        }

        $user = Auth::user();
        $requester = request()->ip() ?? 'unknown';
        $identity = $user instanceof User ? (string) $user->getAuthIdentifier() : 'guest';
        $limiterKeys = ['order-checkout:ip:'.hash('sha256', $requester)];

        if ($user instanceof User) {
            $limiterKeys[] = 'order-checkout:user:'.$identity;
        }

        foreach ($limiterKeys as $limiterKey) {
            if (! RateLimiter::tooManyAttempts($limiterKey, 5)) {
                continue;
            }

            $this->addError(
                'checkout',
                'Trop de tentatives. Réessayez dans '.RateLimiter::availableIn($limiterKey).' seconde(s).',
            );

            return;
        }

        foreach ($limiterKeys as $limiterKey) {
            RateLimiter::hit($limiterKey, 60);
        }

        $validated = $this->validate([
            'selectedPlanPriceId' => ['required', 'integer', 'min:1', 'exists:plan_prices,id'],
            'currencyCode' => [
                'required',
                'string',
                Rule::exists('currencies', 'code')->where('is_active', true),
            ],
            'billingName' => $user instanceof User
                ? ['nullable', 'string', 'max:255']
                : ['required', 'string', 'max:255'],
            'billingEmail' => $user instanceof User
                ? ['nullable', 'string', 'max:255']
                : ['required', 'email', 'max:255'],
        ]);

        $accessToken = Str::random(64);
        $order = app(CreateOrderFromPlanPrice::class)->execute(
            (int) $validated['selectedPlanPriceId'],
            $validated['currencyCode'],
            [
                'name' => $validated['billingName'] ?? '',
                'email' => $validated['billingEmail'] ?? '',
            ],
            $user instanceof User ? $user : null,
            guestAccessToken: $accessToken,
        );
        $order->loadMissing(['currency', 'items.currency']);

        $orderUrl = URL::temporarySignedRoute(
            'orders.show',
            $order->guest_access_expires_at,
            ['publicId' => $order->public_id, 'token' => $accessToken],
        );

        $this->orderUrl = $orderUrl;
        $this->orderReference = $order->public_id;
        $this->selectedPlanPriceId = '';
        $this->billingName = '';
        $this->billingEmail = '';

        try {
            Notification::route('mail', $order->customer_snapshot['email'])
                ->notify(new OrderConfirmationNotification($order, $orderUrl));
        } catch (TransportExceptionInterface $exception) {
            $this->emailDeliveryFailed = true;

            Log::error('Order confirmation email could not be delivered.', [
                'order_public_id' => $order->public_id,
                'exception_type' => $exception::class,
            ]);
        }
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
        $selectedPlanPrice = $plans
            ->flatMap(fn (Plan $plan) => $plan->planPrices)
            ->firstWhere('id', (int) $this->selectedPlanPriceId);

        return view('livewire.catalog.public-offers', [
            'currencies' => $currencies,
            'currency' => $currency,
            'moneyFormatter' => $moneyFormatter,
            'plans' => $plans,
            'selectedPlanPrice' => $selectedPlanPrice,
        ]);
    }
}

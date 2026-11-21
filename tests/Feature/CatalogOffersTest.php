<?php

namespace Tests\Feature;

use App\Domain\Catalog\Actions\CreatePlanPrice;
use App\Domain\Catalog\Actions\SetDefaultCurrency;
use App\Domain\Catalog\Enums\BillingPeriodUnit;
use App\Domain\Catalog\Enums\PlanStatus;
use App\Domain\Catalog\Services\MoneyFormatter;
use App\Domain\Orders\Actions\CreateOrderFromPlanPrice;
use App\Livewire\Catalog\PublicOffers;
use App\Models\Currency;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CatalogOffersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_public_offers_show_only_published_plans_and_effective_prices_in_the_default_currency(): void
    {
        $admin = $this->admin();
        $usd = Currency::factory()->create();
        $eur = Currency::factory()->create([
            'code' => 'EUR',
            'name' => 'Euro',
            'symbol' => '€',
        ]);
        app(SetDefaultCurrency::class)->execute($admin, $usd->code);

        $publishedPlan = Plan::factory()->create([
            'name' => 'Published Offer',
            'sort_order' => 1,
        ]);
        $firstPlan = Plan::factory()->create([
            'name' => 'First Offer',
            'sort_order' => 0,
        ]);
        $hiddenPlan = Plan::factory()->create([
            'name' => 'Hidden Offer',
            'is_public' => false,
        ]);
        $draftPlan = Plan::factory()->create([
            'name' => 'Draft Offer',
            'status' => PlanStatus::Draft,
        ]);

        PlanPrice::factory()->create([
            'plan_id' => $publishedPlan->id,
            'currency_code' => $usd->code,
            'billing_period_unit' => BillingPeriodUnit::Month,
            'billing_period_count' => 1,
            'amount_minor' => 1999,
        ]);
        PlanPrice::factory()->create([
            'plan_id' => $firstPlan->id,
            'currency_code' => $usd->code,
        ]);
        PlanPrice::factory()->create([
            'plan_id' => $publishedPlan->id,
            'currency_code' => $usd->code,
            'billing_period_unit' => BillingPeriodUnit::Week,
            'billing_period_count' => 4,
            'amount_minor' => 5500,
        ]);
        PlanPrice::factory()->create([
            'plan_id' => $publishedPlan->id,
            'currency_code' => $usd->code,
            'amount_minor' => 900,
            'effective_from' => now()->subDays(3),
            'effective_until' => now()->subDay(),
        ]);
        PlanPrice::factory()->create([
            'plan_id' => $publishedPlan->id,
            'currency_code' => $eur->code,
            'amount_minor' => 1800,
        ]);
        PlanPrice::factory()->create([
            'plan_id' => $hiddenPlan->id,
            'currency_code' => $usd->code,
        ]);
        PlanPrice::factory()->create([
            'plan_id' => $draftPlan->id,
            'currency_code' => $usd->code,
        ]);

        $this->get('/offers')
            ->assertOk()
            ->assertSee('Published Offer')
            ->assertSeeInOrder(['First Offer', 'Published Offer'])
            ->assertSee('1 mois')
            ->assertSee('4 semaines')
            ->assertSee('19.99 USD')
            ->assertDontSee('Hidden Offer')
            ->assertDontSee('Draft Offer')
            ->assertDontSee('9.00 USD')
            ->assertDontSee('18.00 EUR');
    }

    public function test_public_currency_selection_rejects_an_inactive_currency(): void
    {
        $admin = $this->admin();
        $activeCurrency = Currency::factory()->create();
        Currency::factory()->create([
            'code' => 'EUR',
            'name' => 'Euro',
            'is_active' => false,
        ]);
        app(SetDefaultCurrency::class)->execute($admin, $activeCurrency->code);

        Livewire::test(PublicOffers::class)
            ->assertSet('currencyCode', $activeCurrency->code)
            ->set('currencyCode', 'EUR')
            ->assertHasErrors('currencyCode');
    }

    public function test_order_snapshots_remain_unchanged_when_the_plan_and_price_change(): void
    {
        $admin = $this->admin();
        $currency = Currency::factory()->create();
        $plan = Plan::factory()->create(['name' => 'Original Plan Name']);
        $price = PlanPrice::factory()->create([
            'plan_id' => $plan->id,
            'currency_code' => $currency->code,
            'billing_period_unit' => BillingPeriodUnit::Month,
            'billing_period_count' => 3,
            'amount_minor' => 4200,
        ]);

        $order = app(CreateOrderFromPlanPrice::class)->execute(
            $price->id,
            $currency->code,
            ['name' => 'Guest Customer', 'email' => 'guest@example.test'],
        );

        $item = $order->items->sole();
        $this->assertSame('Original Plan Name', $item->plan_name_snapshot);
        $this->assertSame(4200, $item->unit_amount_minor);
        $this->assertSame(3, $item->billing_period_count);

        $plan->update(['name' => 'Renamed Plan']);

        app(CreatePlanPrice::class)->execute($admin, [
            'plan_id' => $plan->id,
            'currency_code' => $currency->code,
            'billing_period_unit' => BillingPeriodUnit::Month->value,
            'billing_period_count' => 3,
            'amount_minor' => 9900,
            'effective_from' => now()->addMinute()->toDateTimeString(),
            'effective_until' => null,
        ]);

        $item->refresh();
        $this->assertSame('Original Plan Name', $item->plan_name_snapshot);
        $this->assertSame(4200, $item->unit_amount_minor);
        $this->assertSame($currency->code, $item->currency_code);
        $this->assertSame($order->currency_code, $item->currency_code);
    }

    public function test_order_creation_rejects_unpublished_plans_and_a_different_currency(): void
    {
        $currency = Currency::factory()->create();
        Currency::factory()->create([
            'code' => 'EUR',
            'name' => 'Euro',
        ]);
        $plan = Plan::factory()->create(['is_public' => false]);
        $price = PlanPrice::factory()->create([
            'plan_id' => $plan->id,
            'currency_code' => $currency->code,
        ]);

        try {
            app(CreateOrderFromPlanPrice::class)->execute(
                $price->id,
                $currency->code,
                ['name' => 'Guest Customer', 'email' => 'guest@example.test'],
            );
            $this->fail('An unpublished plan must not be ordered.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('planPrice', $exception->errors());
        }

        $plan->update(['is_public' => true]);

        try {
            app(CreateOrderFromPlanPrice::class)->execute(
                $price->id,
                'EUR',
                ['name' => 'Guest Customer', 'email' => 'guest@example.test'],
            );
            $this->fail('An order must use the price currency.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('currencyCode', $exception->errors());
        }

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_plan_price_creation_rejects_inactive_currencies_and_closes_the_previous_period(): void
    {
        $admin = $this->admin();
        $currency = Currency::factory()->create();
        $inactiveCurrency = Currency::factory()->create([
            'code' => 'EUR',
            'name' => 'Euro',
            'is_active' => false,
        ]);
        $plan = Plan::factory()->create();
        $existingPrice = PlanPrice::factory()->create([
            'plan_id' => $plan->id,
            'currency_code' => $currency->code,
            'billing_period_count' => 6,
        ]);

        try {
            app(CreatePlanPrice::class)->execute($admin, [
                'plan_id' => $plan->id,
                'currency_code' => $inactiveCurrency->code,
                'billing_period_unit' => BillingPeriodUnit::Month->value,
                'billing_period_count' => 6,
                'amount_minor' => 5000,
                'effective_from' => now()->addDay()->toDateTimeString(),
                'effective_until' => null,
            ]);
            $this->fail('An inactive currency must not be used for a new plan price.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('currency_code', $exception->errors());
        }

        $effectiveFrom = now()->addDay()->startOfSecond();
        app(CreatePlanPrice::class)->execute($admin, [
            'plan_id' => $plan->id,
            'currency_code' => $currency->code,
            'billing_period_unit' => BillingPeriodUnit::Month->value,
            'billing_period_count' => 6,
            'amount_minor' => 7500,
            'effective_from' => $effectiveFrom->toDateTimeString(),
            'effective_until' => null,
        ]);

        $this->assertTrue($existingPrice->fresh()->effective_until->equalTo($effectiveFrom->copy()->subSecond()));
    }

    public function test_money_formatter_keeps_minor_unit_arithmetic_integers(): void
    {
        $currency = Currency::factory()->create([
            'minor_unit' => 2,
        ]);

        $this->assertSame('12,345.67 USD', app(MoneyFormatter::class)->format(1234567, $currency));
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findByName('admin', 'web'));

        return $admin;
    }
}

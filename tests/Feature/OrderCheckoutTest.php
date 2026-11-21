<?php

namespace Tests\Feature;

use App\Domain\Catalog\Enums\BillingPeriodUnit;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Notifications\OrderConfirmationNotification;
use App\Livewire\Catalog\PublicOffers;
use App\Models\Currency;
use App\Models\Order;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class OrderCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_guest_checkout_creates_a_pending_order_and_sends_a_signed_confirmation_link(): void
    {
        Notification::fake();

        [$currency, $price] = $this->availablePrice();

        $component = Livewire::test(PublicOffers::class)
            ->set('currencyCode', $currency->code)
            ->call('choosePrice', $price->id)
            ->set('billingName', 'Guest Customer')
            ->set('billingEmail', 'guest@example.test')
            ->call('placeOrder')
            ->assertHasNoErrors();

        $order = Order::query()->sole();
        $item = $order->items()->sole();

        $this->assertSame(OrderStatus::PendingPayment, $order->status);
        $this->assertTrue(Str::isUlid($order->public_id));
        $this->assertNull($order->user_id);
        $this->assertSame('Guest Customer', $order->customer_snapshot['name']);
        $this->assertSame('guest@example.test', $order->customer_snapshot['email']);
        $this->assertSame('Preptv Premium', $item->plan_name_snapshot);
        $this->assertSame(2501, $item->unit_amount_minor);
        $this->assertSame($currency->code, $item->currency_code);
        $this->assertTrue($order->expires_at->isFuture());
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $order->guest_access_token_hash);
        $this->assertTrue($order->guest_access_expires_at->isFuture());
        $this->assertDatabaseCount('subscriptions', 0);

        $orderUrl = $component->get('orderUrl');
        $this->assertNotSame('', $orderUrl);
        $this->assertStringContainsString('signature=', $orderUrl);

        $pathParts = explode('/', (string) parse_url($orderUrl, PHP_URL_PATH));
        $token = end($pathParts);
        $this->assertSame(hash('sha256', $token), $order->guest_access_token_hash);

        $this->get($orderUrl)
            ->assertOk()
            ->assertSee($order->public_id)
            ->assertSee('Preptv Premium')
            ->assertSee('En attente de paiement');
        $this->get('/orders/'.$order->public_id.'/'.$token)
            ->assertForbidden();
        $this->get('/orders/'.$order->public_id)
            ->assertNotFound();

        Notification::assertSentOnDemand(OrderConfirmationNotification::class);
    }

    public function test_authenticated_checkout_uses_the_account_identity_not_browser_supplied_identity(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'name' => 'Account Holder',
            'email' => 'account@example.test',
        ]);
        [$currency, $price] = $this->availablePrice();

        $this->actingAs($user);

        Livewire::test(PublicOffers::class)
            ->set('currencyCode', $currency->code)
            ->call('choosePrice', $price->id)
            ->set('billingName', 'Forged Name')
            ->set('billingEmail', 'attacker@example.test')
            ->call('placeOrder')
            ->assertHasNoErrors();

        $order = Order::query()->sole();

        $this->assertSame($user->id, $order->user_id);
        $this->assertSame('Account Holder', $order->customer_snapshot['name']);
        $this->assertSame('account@example.test', $order->customer_snapshot['email']);
        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_checkout_rejects_an_unavailable_price_without_creating_an_order(): void
    {
        [$currency, $price] = $this->availablePrice();
        $price->plan->update(['is_public' => false]);

        Livewire::test(PublicOffers::class)
            ->set('currencyCode', $currency->code)
            ->set('selectedPlanPriceId', (string) $price->id)
            ->set('billingName', 'Guest Customer')
            ->set('billingEmail', 'guest@example.test')
            ->call('placeOrder')
            ->assertHasErrors('planPrice');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_guest_checkout_requires_valid_billing_name_and_email(): void
    {
        [$currency, $price] = $this->availablePrice();

        Livewire::test(PublicOffers::class)
            ->set('currencyCode', $currency->code)
            ->call('choosePrice', $price->id)
            ->set('billingName', '')
            ->set('billingEmail', 'not-an-email')
            ->call('placeOrder')
            ->assertHasErrors(['billingName', 'billingEmail']);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_rate_limits_repeated_submissions_by_requester(): void
    {
        $currency = Currency::factory()->create();
        $key = 'order-checkout:ip:'.hash('sha256', '127.0.0.1');
        RateLimiter::clear($key);

        $component = Livewire::test(PublicOffers::class)
            ->set('currencyCode', $currency->code);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $component->call('placeOrder');
        }

        $component->call('placeOrder')
            ->assertHasErrors('checkout');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_pending_orders_expire_without_changing_paid_orders(): void
    {
        $currency = Currency::factory()->create();
        $expiredPendingOrder = Order::factory()->create([
            'currency_code' => $currency->code,
            'expires_at' => now()->subMinute(),
        ]);
        $activePendingOrder = Order::factory()->create([
            'currency_code' => $currency->code,
            'expires_at' => now()->addHour(),
        ]);
        $paidOrder = Order::factory()->create([
            'currency_code' => $currency->code,
            'status' => OrderStatus::Paid,
            'expires_at' => now()->subMinute(),
        ]);
        $legacyPendingOrder = Order::factory()->create([
            'currency_code' => $currency->code,
            'expires_at' => null,
            'created_at' => now()->subDays(2),
        ]);

        $this->artisan('orders:expire-pending')
            ->expectsOutput('2 commande(s) expirée(s).')
            ->assertExitCode(0);

        $this->assertSame(OrderStatus::Expired, $expiredPendingOrder->fresh()->status);
        $this->assertSame(OrderStatus::PendingPayment, $activePendingOrder->fresh()->status);
        $this->assertSame(OrderStatus::Paid, $paidOrder->fresh()->status);
        $this->assertSame(OrderStatus::Expired, $legacyPendingOrder->fresh()->status);
    }

    public function test_a_signed_order_url_with_an_invalid_secret_is_not_found(): void
    {
        $currency = Currency::factory()->create();
        $order = Order::factory()->create([
            'currency_code' => $currency->code,
            'guest_access_token_hash' => hash('sha256', Str::random(64)),
            'guest_access_expires_at' => now()->addDay(),
        ]);
        $url = URL::temporarySignedRoute('orders.show', now()->addDay(), [
            'publicId' => $order->public_id,
            'token' => Str::random(64),
        ]);

        $this->get($url)->assertNotFound();
    }

    public function test_order_can_be_abandoned_only_through_its_signed_secret_url(): void
    {
        $currency = Currency::factory()->create();
        $token = Str::random(64);
        $order = Order::factory()->create([
            'currency_code' => $currency->code,
            'guest_access_token_hash' => hash('sha256', $token),
            'guest_access_expires_at' => now()->addDay(),
            'expires_at' => now()->addDay(),
        ]);
        $url = URL::temporarySignedRoute('orders.abandon', now()->addDay(), [
            'publicId' => $order->public_id,
            'token' => $token,
        ]);

        $this->post($url)->assertRedirect();

        $this->assertSame(OrderStatus::Abandoned, $order->fresh()->status);
    }

    public function test_viewing_an_expired_order_does_not_mutate_it_on_get(): void
    {
        $currency = Currency::factory()->create();
        $token = Str::random(64);
        $order = Order::factory()->create([
            'currency_code' => $currency->code,
            'guest_access_token_hash' => hash('sha256', $token),
            'guest_access_expires_at' => now()->addDay(),
            'expires_at' => now()->subMinute(),
        ]);
        $url = URL::temporarySignedRoute('orders.show', now()->addDay(), [
            'publicId' => $order->public_id,
            'token' => $token,
        ]);

        $this->get($url)
            ->assertOk()
            ->assertSee('Cette commande a expiré');

        $this->assertSame(OrderStatus::PendingPayment, $order->fresh()->status);
    }

    /**
     * @return array{Currency, PlanPrice}
     */
    private function availablePrice(): array
    {
        $currency = Currency::factory()->create();
        $plan = Plan::factory()->create([
            'name' => 'Preptv Premium',
            'is_public' => true,
        ]);
        $price = PlanPrice::factory()->create([
            'plan_id' => $plan->id,
            'currency_code' => $currency->code,
            'billing_period_unit' => BillingPeriodUnit::Month,
            'billing_period_count' => 3,
            'amount_minor' => 2501,
        ]);

        return [$currency, $price];
    }
}

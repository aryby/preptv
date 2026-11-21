<?php

namespace Tests\Feature;

use App\Domain\Billing\Enums\InvoiceStatus;
use App\Domain\Billing\Notifications\InvoiceIssuedNotification;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Payments\Actions\RefundPayment;
use App\Domain\Payments\Enums\PaymentRefundStatus;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Enums\PaymentWebhookEventStatus;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Models\PaymentWebhookEvent;
use App\Models\Plan;
use App\Models\PlanPrice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class PayPalPaymentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.paypal.client_id' => 'client-id',
            'services.paypal.client_secret' => 'client-secret',
            'services.paypal.webhook_id' => 'webhook-id',
            'services.paypal.mode' => 'sandbox',
            'billing.seller.name' => 'Example Seller',
            'billing.seller.address' => '1 Example Street, Paris',
            'billing.seller.email' => 'billing@example.test',
            'billing.invoice.number_prefix' => 'INV',
            'billing.invoice.number_format' => '{prefix}-{year}-{sequence}',
            'billing.invoice.sequence_padding' => 6,
            'billing.invoice.download_link_lifetime_minutes' => 10080,
        ]);
        Notification::fake();
        Storage::fake('local');
    }

    public function test_checkout_uses_order_amount_and_currency_and_browser_return_does_not_confirm_payment(): void
    {
        $order = $this->pendingOrder();
        $token = $this->authorizeOrder($order);
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'access-token']),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response([
                'id' => 'PAYPAL-ORDER-1',
                'links' => [['rel' => 'approve', 'href' => 'https://www.sandbox.paypal.com/checkoutnow?token=PAYPAL-ORDER-1']],
            ], 201),
        ]);

        $checkoutUrl = URL::temporarySignedRoute('payments.paypal.start', now()->addHour(), [
            'publicId' => $order->public_id,
            'token' => $token,
        ]);
        $this->post($checkoutUrl)
            ->assertRedirect('https://www.sandbox.paypal.com/checkoutnow?token=PAYPAL-ORDER-1');
        $this->post($checkoutUrl)
            ->assertRedirect('https://www.sandbox.paypal.com/checkoutnow?token=PAYPAL-ORDER-1');

        $payment = Payment::query()->sole();
        $this->assertDatabaseCount('payments', 1);
        $this->assertSame(2501, $payment->amount_minor);
        $this->assertSame('EUR', $payment->currency_code);
        $this->assertSame(PaymentStatus::Pending, $payment->status);
        $this->assertSame(OrderStatus::PendingPayment, $order->fresh()->status);
        $this->assertDatabaseCount('subscriptions', 0);

        Http::assertSent(function (HttpRequest $request): bool {
            if (! str_ends_with($request->url(), '/v2/checkout/orders')) {
                return true;
            }

            return $request['intent'] === 'CAPTURE'
                && $request['purchase_units'][0]['amount'] === ['currency_code' => 'EUR', 'value' => '25.01']
                && $request->hasHeader('PayPal-Request-Id');
        });

        $this->get(route('payments.paypal.return'))
            ->assertOk()
            ->assertSee('ne sera marquée payée qu’après vérification');
        $this->assertSame(OrderStatus::PendingPayment, $order->fresh()->status);
        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
    }

    public function test_checkout_secret_is_required_and_an_invalid_secret_cannot_create_a_payment(): void
    {
        $order = $this->pendingOrder();
        $this->authorizeOrder($order);
        $url = URL::temporarySignedRoute('payments.paypal.start', now()->addHour(), [
            'publicId' => $order->public_id,
            'token' => Str::random(64),
        ]);

        $this->post($url)->assertNotFound();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_valid_completed_webhook_marks_payment_and_order_paid_once(): void
    {
        [$order, $payment] = $this->pendingPayment('PAYPAL-ORDER-2', 'CAPTURE-2');
        $this->fakeWebhookVerification('SUCCESS');

        $this->postJson(route('webhooks.paypal'), $this->captureCompletedEvent('EVENT-2', 'PAYPAL-ORDER-2', 'CAPTURE-2'), $this->webhookHeaders())
            ->assertOk()
            ->assertJsonPath('status', 'processed');

        $this->assertSame(PaymentStatus::Succeeded, $payment->fresh()->status);
        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
        $this->assertSame(PaymentWebhookEventStatus::Processed, $payment->webhookEvents()->sole()->status);
        $this->assertSame(1, $payment->webhookEvents()->sole()->attempt_count);
        $invoice = Invoice::query()->with('items')->sole();
        $this->assertSame(InvoiceStatus::Paid, $invoice->status);
        $this->assertSame(2501, $invoice->total_minor);
        $this->assertSame('Example Seller', $invoice->seller_snapshot['name']);
        $this->assertSame('Guest Customer', $invoice->buyer_snapshot['name']);
        $this->assertSame('Snapshot service — 3 mois', $invoice->items->sole()->description_snapshot);
        $this->assertSame('EUR', $invoice->items->sole()->currency_code);
        $this->assertNotNull($invoice->pdf_sha256);
        $this->assertNotNull($invoice->email_sent_at);
        Storage::disk('local')->assertExists($invoice->pdf_path);
        Notification::assertSentOnDemand(InvoiceIssuedNotification::class);
        $this->assertDatabaseCount('subscriptions', 0);

        Http::assertSent(fn (HttpRequest $request): bool => ! str_ends_with($request->url(), '/v1/notifications/verify-webhook-signature')
            || ($request['auth_algo'] === 'SHA256withRSA'
                && $request['transmission_id'] === 'TRANSMISSION-1'
                && $request['webhook_id'] === 'webhook-id'
                && $request['webhook_event']['id'] === 'EVENT-2'));
    }

    public function test_invalid_webhook_signature_is_rejected_and_does_not_change_payment(): void
    {
        [$order, $payment] = $this->pendingPayment('PAYPAL-ORDER-3', 'CAPTURE-3');
        $this->fakeWebhookVerification('FAILURE');

        $this->postJson(route('webhooks.paypal'), $this->captureCompletedEvent('EVENT-3', 'PAYPAL-ORDER-3', 'CAPTURE-3'), $this->webhookHeaders())
            ->assertBadRequest()
            ->assertJsonPath('status', 'rejected');

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertSame(OrderStatus::PendingPayment, $order->fresh()->status);
        $this->assertDatabaseHas('payment_webhook_events', ['provider' => 'paypal', 'status' => PaymentWebhookEventStatus::Rejected->value]);
    }

    public function test_duplicate_and_replayed_webhooks_are_idempotent(): void
    {
        [$order, $payment] = $this->pendingPayment('PAYPAL-ORDER-4', 'CAPTURE-4');
        $this->fakeWebhookVerification('SUCCESS');
        $event = $this->captureCompletedEvent('EVENT-4', 'PAYPAL-ORDER-4', 'CAPTURE-4');

        $this->postJson(route('webhooks.paypal'), $event, $this->webhookHeaders())->assertOk();
        $this->postJson(route('webhooks.paypal'), $event, $this->webhookHeaders())
            ->assertOk()
            ->assertJsonPath('status', 'duplicate');

        $replayedEvent = $event;
        $replayedEvent['resource']['amount']['value'] = '99.99';
        $this->postJson(route('webhooks.paypal'), $replayedEvent, $this->webhookHeaders())
            ->assertOk()
            ->assertJsonPath('status', 'rejected');

        $webhook = $payment->webhookEvents()->sole();
        $this->assertSame(PaymentWebhookEventStatus::Processed, $webhook->status);
        $this->assertSame(2, $webhook->attempt_count);
        $this->assertSame(PaymentStatus::Succeeded, $payment->fresh()->status);
        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('payment_webhook_events', 1);
    }

    public function test_approved_event_captures_server_side_but_waits_for_completed_webhook_before_marking_paid(): void
    {
        [$order, $payment] = $this->pendingPayment('PAYPAL-ORDER-5');
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'access-token']),
            'https://api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => 'SUCCESS']),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders/PAYPAL-ORDER-5' => Http::response([
                'id' => 'PAYPAL-ORDER-5',
                'status' => 'APPROVED',
                'purchase_units' => [[
                    'reference_id' => $order->public_id,
                    'custom_id' => $order->public_id,
                    'amount' => ['currency_code' => 'EUR', 'value' => '25.01'],
                ]],
            ]),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders/PAYPAL-ORDER-5/capture' => Http::response([
                'status' => 'COMPLETED',
                'purchase_units' => [['payments' => ['captures' => [['id' => 'CAPTURE-5']]]]],
            ]),
        ]);

        $this->postJson(route('webhooks.paypal'), [
            'id' => 'EVENT-5',
            'event_type' => 'CHECKOUT.ORDER.APPROVED',
            'resource' => ['id' => 'PAYPAL-ORDER-5', 'status' => 'APPROVED'],
        ], $this->webhookHeaders())->assertOk();

        $this->assertSame(PaymentStatus::Processing, $payment->fresh()->status);
        $this->assertSame('CAPTURE-5', $payment->fresh()->provider_capture_id);
        $this->assertSame(OrderStatus::PendingPayment, $order->fresh()->status);
        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_webhook_rejects_mismatched_amount_without_confirming_payment(): void
    {
        [$order, $payment] = $this->pendingPayment('PAYPAL-ORDER-6', 'CAPTURE-6');
        $this->fakeWebhookVerification('SUCCESS');
        $event = $this->captureCompletedEvent('EVENT-6', 'PAYPAL-ORDER-6', 'CAPTURE-6');
        $event['resource']['amount']['value'] = '25.02';

        $this->postJson(route('webhooks.paypal'), $event, $this->webhookHeaders())
            ->assertOk()
            ->assertJsonPath('status', 'rejected');

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertSame(OrderStatus::PendingPayment, $order->fresh()->status);
        $this->assertSame(
            PaymentWebhookEventStatus::Rejected,
            PaymentWebhookEvent::query()->where('provider_event_id', 'EVENT-6')->sole()->status,
        );
    }

    public function test_webhook_rejects_a_provider_status_that_conflicts_with_the_event_type(): void
    {
        [$order, $payment] = $this->pendingPayment('PAYPAL-ORDER-11', 'CAPTURE-11');
        $this->fakeWebhookVerification('SUCCESS');
        $event = $this->captureCompletedEvent('EVENT-11', 'PAYPAL-ORDER-11', 'CAPTURE-11');
        $event['resource']['status'] = 'PENDING';

        $this->postJson(route('webhooks.paypal'), $event, $this->webhookHeaders())
            ->assertOk()
            ->assertJsonPath('status', 'rejected');

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertSame(OrderStatus::PendingPayment, $order->fresh()->status);
        $this->assertSame(
            'provider_status_mismatch',
            PaymentWebhookEvent::query()->where('provider_event_id', 'EVENT-11')->sole()->failure_code,
        );
    }

    public function test_pending_denied_and_cancelled_events_transition_payment_without_activating_an_order(): void
    {
        [$order, $payment] = $this->pendingPayment('PAYPAL-ORDER-8', 'CAPTURE-8');
        $this->fakeWebhookVerification('SUCCESS');

        $pending = $this->captureCompletedEvent('EVENT-8A', 'PAYPAL-ORDER-8', 'CAPTURE-8');
        $pending['event_type'] = 'PAYMENT.CAPTURE.PENDING';
        $pending['resource']['status'] = 'PENDING';
        $this->postJson(route('webhooks.paypal'), $pending, $this->webhookHeaders())
            ->assertOk()
            ->assertJsonPath('status', 'processed');
        $this->assertSame(PaymentStatus::Processing, $payment->fresh()->status);

        $denied = $this->captureCompletedEvent('EVENT-8B', 'PAYPAL-ORDER-8', 'CAPTURE-8');
        $denied['event_type'] = 'PAYMENT.CAPTURE.DENIED';
        $denied['resource']['status'] = 'DENIED';
        $this->postJson(route('webhooks.paypal'), $denied, $this->webhookHeaders())
            ->assertOk()
            ->assertJsonPath('status', 'processed');
        $this->assertSame(PaymentStatus::Failed, $payment->fresh()->status);

        $this->postJson(route('webhooks.paypal'), [
            'id' => 'EVENT-8C',
            'event_type' => 'CHECKOUT.ORDER.VOIDED',
            'resource' => ['id' => 'PAYPAL-ORDER-8', 'status' => 'VOIDED'],
        ], $this->webhookHeaders())->assertOk();

        $this->assertSame(PaymentStatus::Cancelled, $payment->fresh()->status);
        $this->assertSame(OrderStatus::PendingPayment, $order->fresh()->status);
        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_refund_webhook_reconciles_only_a_matching_refund_record(): void
    {
        [$order, $payment] = $this->pendingPayment('PAYPAL-ORDER-9', 'CAPTURE-9', PaymentStatus::Succeeded, OrderStatus::Paid);
        $refund = Model::unguarded(fn (): PaymentRefund => PaymentRefund::query()->create([
            'payment_id' => $payment->getKey(),
            'provider_refund_id' => 'PAYPAL-REFUND-9',
            'idempotency_key' => 'refund-key-9',
            'status' => PaymentRefundStatus::Processing,
            'amount_minor' => 1000,
            'currency_code' => 'EUR',
        ]));
        $this->fakeWebhookVerification('SUCCESS');

        $this->postJson(route('webhooks.paypal'), [
            'id' => 'EVENT-9',
            'event_type' => 'PAYMENT.CAPTURE.REFUNDED',
            'resource' => [
                'id' => 'PAYPAL-REFUND-9',
                'status' => 'COMPLETED',
                'amount' => ['currency_code' => 'EUR', 'value' => '10.00'],
                'supplementary_data' => ['related_ids' => ['order_id' => 'PAYPAL-ORDER-9']],
            ],
        ], $this->webhookHeaders())->assertOk();

        $this->assertSame(PaymentRefundStatus::Succeeded, $refund->fresh()->status);
        $this->assertSame(PaymentStatus::PartiallyRefunded, $payment->fresh()->status);
        $this->assertSame(OrderStatus::PartiallyRefunded, $order->fresh()->status);
    }

    public function test_paypal_capture_reversal_is_recorded_as_a_reversed_payment_and_order(): void
    {
        [$order, $payment] = $this->pendingPayment('PAYPAL-ORDER-10', 'CAPTURE-10', PaymentStatus::Succeeded, OrderStatus::Paid);
        $this->fakeWebhookVerification('SUCCESS');
        $event = $this->captureCompletedEvent('EVENT-10', 'PAYPAL-ORDER-10', 'CAPTURE-10');
        $event['event_type'] = 'CHECKOUT.PAYMENT-APPROVAL.REVERSED';
        $event['resource']['status'] = 'REVERSED';

        $this->postJson(route('webhooks.paypal'), $event, $this->webhookHeaders())
            ->assertOk()
            ->assertJsonPath('status', 'processed');

        $this->assertSame(PaymentStatus::Reversed, $payment->fresh()->status);
        $this->assertSame(OrderStatus::Reversed, $order->fresh()->status);
        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_refund_uses_provider_capture_and_idempotency_and_updates_financial_status(): void
    {
        [$order, $payment] = $this->pendingPayment('PAYPAL-ORDER-7', 'CAPTURE-7', PaymentStatus::Succeeded, OrderStatus::Paid);
        $this->fakeRefundResponse();

        $refund = app(RefundPayment::class)->execute($payment, 2501, 'EUR', 'refund-key-7');

        $this->assertSame('PAYPAL-REFUND-7', $refund->provider_refund_id);
        $this->assertSame(PaymentRefundStatus::Succeeded, $refund->status);
        $this->assertSame(PaymentStatus::Refunded, $payment->fresh()->status);
        $this->assertSame(OrderStatus::Refunded, $order->fresh()->status);
        $this->assertDatabaseCount('subscriptions', 0);

        Http::assertSent(fn (HttpRequest $request): bool => str_ends_with($request->url(), '/v2/payments/captures/CAPTURE-7/refund')
            ? $request['amount'] === ['value' => '25.01', 'currency_code' => 'EUR']
                && $request->hasHeader('PayPal-Request-Id', 'refund-key-7')
            : true);
    }

    private function pendingOrder(): Order
    {
        $currency = Currency::factory()->create(['code' => 'EUR', 'minor_unit' => 2]);
        $plan = Plan::factory()->create(['name' => 'Current catalog name']);
        $price = PlanPrice::factory()->create([
            'plan_id' => $plan->getKey(),
            'currency_code' => $currency->code,
            'amount_minor' => 2400,
            'billing_period_count' => 3,
        ]);

        $order = Model::unguarded(fn (): Order => Order::factory()->create([
            'currency_code' => $currency->code,
            'subtotal_minor' => 2400,
            'tax_minor' => 101,
            'total_minor' => 2501,
            'expires_at' => now()->addDay(),
            'customer_snapshot' => [
                'name' => 'Guest Customer',
                'email' => 'guest@example.test',
                'billing_address' => ['line1' => '2 Buyer Road', 'city' => 'Paris'],
            ],
        ]));

        Model::unguarded(fn (): OrderItem => OrderItem::query()->create([
            'order_id' => $order->getKey(),
            'line_number' => 1,
            'plan_id' => $plan->getKey(),
            'plan_price_id' => $price->getKey(),
            'currency_code' => $currency->code,
            'plan_name_snapshot' => 'Snapshot service',
            'description_snapshot' => 'Description captured when ordered',
            'billing_period_unit' => 'month',
            'billing_period_count' => 3,
            'quantity' => 1,
            'unit_amount_minor' => 2400,
            'subtotal_minor' => 2400,
            'tax_rate_basis_points' => 420,
            'tax_minor' => 101,
            'tax_label_snapshot' => 'Tax snapshot',
            'total_minor' => 2501,
        ]));

        return $order;
    }

    private function authorizeOrder(Order $order): string
    {
        $token = Str::random(64);
        $order->forceFill([
            'guest_access_token_hash' => hash('sha256', $token),
            'guest_access_expires_at' => now()->addDay(),
        ])->save();

        return $token;
    }

    /**
     * @return array{Order, Payment}
     */
    private function pendingPayment(
        string $providerOrderId,
        ?string $captureId = null,
        PaymentStatus $paymentStatus = PaymentStatus::Pending,
        OrderStatus $orderStatus = OrderStatus::PendingPayment,
    ): array {
        $order = $this->pendingOrder();
        $order->forceFill(['status' => $orderStatus])->save();
        $payment = Model::unguarded(fn (): Payment => Payment::query()->create([
            'order_id' => $order->getKey(),
            'provider' => 'paypal',
            'provider_payment_id' => $providerOrderId,
            'provider_capture_id' => $captureId,
            'idempotency_key' => (string) Str::uuid(),
            'status' => $paymentStatus,
            'amount_minor' => 2501,
            'currency_code' => 'EUR',
        ]));

        return [$order, $payment];
    }

    private function fakeWebhookVerification(string $status): void
    {
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'access-token']),
            'https://api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature' => Http::response(['verification_status' => $status]),
        ]);
    }

    private function fakeRefundResponse(): void
    {
        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'access-token']),
            'https://api-m.sandbox.paypal.com/v2/payments/captures/CAPTURE-7/refund' => Http::response([
                'id' => 'PAYPAL-REFUND-7',
                'status' => 'COMPLETED',
            ]),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function captureCompletedEvent(string $eventId, string $orderId, string $captureId): array
    {
        return [
            'id' => $eventId,
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'id' => $captureId,
                'status' => 'COMPLETED',
                'amount' => ['currency_code' => 'EUR', 'value' => '25.01'],
                'supplementary_data' => ['related_ids' => ['order_id' => $orderId]],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function webhookHeaders(): array
    {
        return [
            'PAYPAL-AUTH-ALGO' => 'SHA256withRSA',
            'PAYPAL-CERT-URL' => 'https://api.paypal.com/certs/CERT-1',
            'PAYPAL-TRANSMISSION-ID' => 'TRANSMISSION-1',
            'PAYPAL-TRANSMISSION-SIG' => 'signed-value',
            'PAYPAL-TRANSMISSION-TIME' => now()->toIso8601String(),
        ];
    }
}

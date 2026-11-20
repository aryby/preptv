<?php

namespace Tests\Feature;

use App\Domain\Billing\Enums\CreditNoteStatus;
use App\Domain\Billing\Enums\InvoiceStatus;
use App\Domain\Catalog\Enums\BillingPeriodUnit;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Payments\Enums\PaymentRefundStatus;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Enums\PaymentWebhookEventStatus;
use App\Domain\Resellers\Enums\ResellerStatus;
use App\Domain\Sessions\Enums\StreamingSessionStatus;
use App\Domain\Subscriptions\Enums\SubscriptionStatus;
use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\CustomerProfile;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Models\PaymentWebhookEvent;
use App\Models\PlanPrice;
use App\Models\ResellerProfile;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ModelRelationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_profile_relations_are_one_to_one(): void
    {
        $user = User::factory()->create();
        $customerProfile = CustomerProfile::unguarded(fn (): CustomerProfile => CustomerProfile::query()->create([
            'user_id' => $user->id,
            'phone' => '+12025550123',
            'billing_address' => ['country' => 'US'],
        ]));
        $resellerProfile = ResellerProfile::unguarded(fn (): ResellerProfile => ResellerProfile::query()->create([
            'user_id' => $user->id,
            'business_name' => 'Example Reseller',
            'status' => ResellerStatus::Active,
            'commission_basis_points' => 500,
        ]));

        $this->assertTrue($user->customerProfile->is($customerProfile));
        $this->assertTrue($user->resellerProfile->is($resellerProfile));
        $this->assertSame(['country' => 'US'], $customerProfile->billing_address);
    }

    public function test_order_item_relations_preserve_the_purchase_price_snapshot(): void
    {
        $planPrice = PlanPrice::factory()->create();
        $order = Order::factory()->create([
            'currency_code' => $planPrice->currency_code,
        ]);
        $orderItem = $this->createOrderItem($order, $planPrice);

        $loadedOrder = Order::query()
            ->with(['items.plan', 'items.planPrice.currency'])
            ->findOrFail($order->id);
        $loadedItem = $loadedOrder->items->sole();

        $this->assertTrue($loadedOrder->relationLoaded('items'));
        $this->assertTrue($loadedItem->relationLoaded('plan'));
        $this->assertTrue($loadedItem->relationLoaded('planPrice'));
        $this->assertTrue($loadedItem->planPrice->relationLoaded('currency'));
        $this->assertSame(OrderStatus::PendingPayment, $loadedOrder->status);
        $this->assertSame(BillingPeriodUnit::Month, $loadedItem->billing_period_unit);
        $this->assertSame(1999, $loadedItem->unit_amount_minor);
        $this->assertSame($orderItem->id, $loadedItem->id);

        DB::table('plan_prices')->where('id', $planPrice->id)->update([
            'amount_minor' => 2999,
        ]);

        $loadedItem->refresh()->load('planPrice');

        $this->assertSame(1999, $loadedItem->unit_amount_minor);
        $this->assertSame(2999, $loadedItem->planPrice->amount_minor);
    }

    public function test_payment_webhook_refund_and_billing_relations_cast_statuses(): void
    {
        $order = Order::factory()->create();
        $paymentId = DB::table('payments')->insertGetId([
            'order_id' => $order->id,
            'provider' => 'example',
            'provider_payment_id' => 'pay-1',
            'idempotency_key' => 'order-1',
            'status' => PaymentStatus::Succeeded->value,
            'amount_minor' => 1999,
            'currency_code' => $order->currency_code,
            'confirmed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('payment_webhook_events')->insert([
            'provider' => 'example',
            'provider_event_id' => 'evt-1',
            'payment_id' => $paymentId,
            'payload_hash' => str_repeat('a', 64),
            'status' => PaymentWebhookEventStatus::Processed->value,
            'attempt_count' => 1,
            'received_at' => now(),
            'processed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('payment_refunds')->insert([
            'payment_id' => $paymentId,
            'provider_refund_id' => 'refund-1',
            'idempotency_key' => 'refund-key-1',
            'status' => PaymentRefundStatus::Succeeded->value,
            'amount_minor' => 500,
            'currency_code' => $order->currency_code,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $invoiceId = DB::table('invoices')->insertGetId([
            'invoice_number' => 'INV-1',
            'order_id' => $order->id,
            'currency_code' => $order->currency_code,
            'status' => InvoiceStatus::Issued->value,
            'subtotal_minor' => 1999,
            'tax_minor' => 0,
            'total_minor' => 1999,
            'buyer_snapshot' => json_encode(['name' => 'Buyer']),
            'seller_snapshot' => json_encode(['name' => 'Seller']),
            'issued_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $invoiceItemId = DB::table('invoice_items')->insertGetId([
            'invoice_id' => $invoiceId,
            'line_number' => 1,
            'description_snapshot' => 'Plan',
            'quantity' => 1,
            'unit_amount_minor' => 1999,
            'subtotal_minor' => 1999,
            'tax_rate_basis_points' => 0,
            'tax_minor' => 0,
            'total_minor' => 1999,
            'currency_code' => $order->currency_code,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $creditNoteId = DB::table('credit_notes')->insertGetId([
            'invoice_id' => $invoiceId,
            'credit_note_number' => 'CN-1',
            'status' => CreditNoteStatus::Issued->value,
            'currency_code' => $order->currency_code,
            'amount_minor' => 500,
            'tax_minor' => 0,
            'total_minor' => 500,
            'buyer_snapshot' => json_encode(['name' => 'Buyer']),
            'seller_snapshot' => json_encode(['name' => 'Seller']),
            'issued_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('credit_note_items')->insert([
            'credit_note_id' => $creditNoteId,
            'invoice_item_id' => $invoiceItemId,
            'line_number' => 1,
            'description_snapshot' => 'Partial credit',
            'quantity' => 1,
            'unit_amount_minor' => 500,
            'subtotal_minor' => 500,
            'tax_rate_basis_points' => 0,
            'tax_minor' => 0,
            'total_minor' => 500,
            'currency_code' => $order->currency_code,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payment = Payment::query()
            ->with(['order', 'currency', 'webhookEvents', 'refunds'])
            ->findOrFail($paymentId);
        $invoice = Invoice::query()
            ->with(['items', 'creditNotes.items'])
            ->findOrFail($invoiceId);

        $this->assertSame(PaymentStatus::Succeeded, $payment->status);
        $this->assertSame(1999, $payment->amount_minor);
        $this->assertInstanceOf(PaymentWebhookEvent::class, $payment->webhookEvents->sole());
        $this->assertInstanceOf(PaymentRefund::class, $payment->refunds->sole());
        $this->assertSame(InvoiceStatus::Issued, $invoice->status);
        $this->assertInstanceOf(InvoiceItem::class, $invoice->items->sole());
        $this->assertInstanceOf(CreditNote::class, $invoice->creditNotes->sole());
        $this->assertInstanceOf(CreditNoteItem::class, $invoice->creditNotes->sole()->items->sole());
    }

    public function test_subscription_history_device_and_streaming_relations_are_eager_loadable(): void
    {
        $planPrice = PlanPrice::factory()->create();
        $order = Order::factory()->create([
            'currency_code' => $planPrice->currency_code,
        ]);
        $orderItem = $this->createOrderItem($order, $planPrice);
        $subscriptionId = DB::table('subscriptions')->insertGetId([
            'order_item_id' => $orderItem->id,
            'plan_id' => $planPrice->plan_id,
            'plan_price_id' => $planPrice->id,
            'currency_code' => $planPrice->currency_code,
            'amount_minor' => 1999,
            'billing_period_unit' => BillingPeriodUnit::Month->value,
            'billing_period_count' => 1,
            'device_limit_snapshot' => 3,
            'concurrent_session_limit_snapshot' => 2,
            'status' => SubscriptionStatus::Active->value,
            'starts_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('subscription_histories')->insert([
            'subscription_id' => $subscriptionId,
            'event_type' => 'activated',
            'from_status' => SubscriptionStatus::Pending->value,
            'to_status' => SubscriptionStatus::Active->value,
            'metadata' => json_encode(['source' => 'test']),
            'occurred_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $deviceId = DB::table('devices')->insertGetId([
            'fingerprint_hash' => hash('sha256', 'test-device'),
            'label' => 'Living room',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $assignmentId = DB::table('device_assignments')->insertGetId([
            'subscription_id' => $subscriptionId,
            'device_id' => $deviceId,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('streaming_sessions')->insert([
            'subscription_id' => $subscriptionId,
            'device_assignment_id' => $assignmentId,
            'provider_session_id' => 'session-1',
            'status' => StreamingSessionStatus::Active->value,
            'started_at' => now(),
            'heartbeat_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $subscription = Subscription::query()
            ->with([
                'histories',
                'deviceAssignments.device',
                'deviceAssignments.streamingSessions',
                'streamingSessions',
            ])
            ->findOrFail($subscriptionId);

        $this->assertSame(SubscriptionStatus::Active, $subscription->status);
        $this->assertSame(SubscriptionStatus::Pending, $subscription->histories->sole()->from_status);
        $this->assertSame('Living room', $subscription->deviceAssignments->sole()->device->label);
        $this->assertSame(StreamingSessionStatus::Active, $subscription->streamingSessions->sole()->status);
        $this->assertTrue($subscription->deviceAssignments->sole()->relationLoaded('streamingSessions'));
    }

    private function createOrderItem(Order $order, PlanPrice $planPrice): OrderItem
    {
        return Model::unguarded(fn (): OrderItem => OrderItem::query()->create([
            'order_id' => $order->id,
            'line_number' => 1,
            'plan_id' => $planPrice->plan_id,
            'plan_price_id' => $planPrice->id,
            'currency_code' => $planPrice->currency_code,
            'plan_name_snapshot' => $planPrice->plan->name,
            'billing_period_unit' => $planPrice->billing_period_unit,
            'billing_period_count' => $planPrice->billing_period_count,
            'quantity' => 1,
            'unit_amount_minor' => 1999,
            'subtotal_minor' => 1999,
            'tax_rate_basis_points' => 0,
            'tax_minor' => 0,
            'total_minor' => 1999,
        ]));
    }
}

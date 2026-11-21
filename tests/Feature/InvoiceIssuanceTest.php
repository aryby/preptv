<?php

namespace Tests\Feature;

use App\Domain\Billing\Actions\IssueInvoiceForPaidOrder;
use App\Domain\Billing\Actions\SendInvoiceEmail;
use App\Domain\Billing\Enums\InvoiceStatus;
use App\Domain\Billing\Exceptions\InvoiceProcessingException;
use App\Domain\Billing\Notifications\InvoiceIssuedNotification;
use App\Domain\Billing\Services\GenerateInvoicePdf;
use App\Domain\Catalog\Enums\BillingPeriodUnit;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Models\Currency;
use App\Models\EmailLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\PlanPrice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use LogicException;
use Tests\TestCase;

class InvoiceIssuanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'billing.seller.name' => 'Example Seller Ltd',
            'billing.seller.address' => "10 Billing Street\nParis, France",
            'billing.seller.email' => 'billing@example.test',
            'billing.seller.tax_identifier' => 'SELLER-TAX-1',
            'billing.seller.registration_number' => 'REG-1',
            'billing.seller.country_code' => 'FR',
            'billing.invoice.number_prefix' => 'TST',
            'billing.invoice.number_format' => '{prefix}-{year}-{sequence}',
            'billing.invoice.sequence_padding' => 6,
            'billing.invoice.download_link_lifetime_minutes' => 60,
        ]);
        Notification::fake();
        Storage::fake('local');
    }

    public function test_paid_order_issues_one_immutable_invoice_from_snapshots_and_sends_pdf(): void
    {
        [$order, $payment, $plan, $price] = $this->paidOrderAndPayment();
        $plan->update(['name' => 'Renamed in catalog']);
        $price->update(['amount_minor' => 99999]);

        $invoice = app(IssueInvoiceForPaidOrder::class)->execute($payment);
        $invoice->refresh()->load('items');

        $this->assertSame(
            'TST-'.now()->format('Y').'-'.str_pad((string) $invoice->getKey(), 6, '0', STR_PAD_LEFT),
            $invoice->invoice_number,
        );
        $this->assertSame(InvoiceStatus::Paid, $invoice->status);
        $this->assertSame($order->currency_code, $invoice->currency_code);
        $this->assertSame(10000, $invoice->subtotal_minor);
        $this->assertSame(500, $invoice->tax_minor);
        $this->assertSame(10500, $invoice->total_minor);
        $this->assertSame($order->customer_snapshot, $invoice->buyer_snapshot);
        $this->assertSame('Example Seller Ltd', $invoice->seller_snapshot['name']);
        $this->assertSame('Snapshot Premium — 3 mois', $invoice->items->sole()->description_snapshot);
        $this->assertSame(10000, $invoice->items->sole()->unit_amount_minor);
        $this->assertSame(420, $invoice->items->sole()->tax_rate_basis_points);
        $this->assertSame(500, $invoice->items->sole()->tax_minor);

        $again = app(IssueInvoiceForPaidOrder::class)->execute($payment);
        $this->assertSame($invoice->getKey(), $again->getKey());
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_items', 1);

        $pdfPath = app(GenerateInvoicePdf::class)->execute($invoice);
        $pdfBytes = Storage::disk('local')->get($pdfPath);
        $this->assertStringStartsWith('%PDF-', $pdfBytes);
        $this->assertSame(hash('sha256', $pdfBytes), $invoice->fresh()->pdf_sha256);

        app(SendInvoiceEmail::class)->execute($invoice->fresh());
        $this->assertNotNull($invoice->fresh()->email_sent_at);
        $this->assertSame('sent', EmailLog::query()->sole()->status->value);
        Notification::assertSentOnDemand(InvoiceIssuedNotification::class);
        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_invoice_pdf_download_requires_a_signed_temporary_link(): void
    {
        [$order, $payment] = $this->paidOrderAndPayment();
        $invoice = app(IssueInvoiceForPaidOrder::class)->execute($payment);
        app(GenerateInvoicePdf::class)->execute($invoice);

        $this->get('/invoices/'.$invoice->getKey().'/pdf')->assertForbidden();

        $url = URL::temporarySignedRoute('invoices.download', now()->addMinutes(10), [
            'invoice' => $invoice->getKey(),
        ]);
        $this->get($url)
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $expiredUrl = URL::temporarySignedRoute('invoices.download', now()->subMinute(), [
            'invoice' => $invoice->getKey(),
        ]);
        $this->get($expiredUrl)->assertForbidden();
    }

    public function test_issued_invoice_and_lines_cannot_be_modified(): void
    {
        [, $payment] = $this->paidOrderAndPayment();
        $invoice = app(IssueInvoiceForPaidOrder::class)->execute($payment);

        try {
            $invoice->forceFill(['total_minor' => 1])->save();
            $this->fail('An issued invoice total must not be changed.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('immutable', $exception->getMessage());
        }

        $item = $invoice->items()->sole();

        try {
            $item->forceFill(['description_snapshot' => 'Changed'])->save();
            $this->fail('An issued invoice line must not be changed.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('immutable', $exception->getMessage());
        }

        $this->assertSame(10500, $invoice->fresh()->total_minor);
        $this->assertSame('Snapshot Premium — 3 mois', $item->fresh()->description_snapshot);
    }

    public function test_missing_seller_identity_prevents_invoice_issuance_without_changing_payment(): void
    {
        [, $payment] = $this->paidOrderAndPayment();
        config([
            'billing.seller.name' => null,
            'billing.seller.address' => null,
        ]);

        try {
            app(IssueInvoiceForPaidOrder::class)->execute($payment);
            $this->fail('Invoice issuance must require seller identity.');
        } catch (InvoiceProcessingException $exception) {
            $this->assertSame('invoice_seller_configuration_incomplete', $exception->failureCode);
        }

        $this->assertSame(PaymentStatus::Succeeded, $payment->fresh()->status);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('invoice_items', 0);
    }

    /**
     * @return array{Order, Payment, Plan, PlanPrice}
     */
    private function paidOrderAndPayment(): array
    {
        $currency = Currency::factory()->create(['code' => 'EUR', 'minor_unit' => 2]);
        $plan = Plan::factory()->create(['name' => 'Current Plan Name']);
        $price = PlanPrice::factory()->create([
            'plan_id' => $plan->getKey(),
            'currency_code' => $currency->code,
            'billing_period_unit' => BillingPeriodUnit::Month,
            'billing_period_count' => 3,
            'amount_minor' => 10000,
        ]);
        $order = Model::unguarded(fn (): Order => Order::factory()->create([
            'currency_code' => $currency->code,
            'status' => OrderStatus::Paid,
            'subtotal_minor' => 10000,
            'tax_minor' => 500,
            'total_minor' => 10500,
            'customer_snapshot' => [
                'name' => 'Snapshot Buyer',
                'email' => 'buyer@example.test',
                'billing_address' => ['line1' => '1 Buyer Road', 'city' => 'Lyon'],
            ],
        ]));
        Model::unguarded(fn (): OrderItem => OrderItem::query()->create([
            'order_id' => $order->getKey(),
            'line_number' => 1,
            'plan_id' => $plan->getKey(),
            'plan_price_id' => $price->getKey(),
            'currency_code' => $currency->code,
            'plan_name_snapshot' => 'Snapshot Premium',
            'description_snapshot' => 'Description captured at purchase time',
            'billing_period_unit' => BillingPeriodUnit::Month,
            'billing_period_count' => 3,
            'quantity' => 1,
            'unit_amount_minor' => 10000,
            'subtotal_minor' => 10000,
            'tax_rate_basis_points' => 420,
            'tax_minor' => 500,
            'tax_label_snapshot' => 'Tax snapshot',
            'total_minor' => 10500,
        ]));
        $payment = Model::unguarded(fn (): Payment => Payment::query()->create([
            'order_id' => $order->getKey(),
            'provider' => 'paypal',
            'provider_payment_id' => 'PAYPAL-'.Str::uuid(),
            'provider_capture_id' => 'CAPTURE-'.Str::uuid(),
            'idempotency_key' => (string) Str::uuid(),
            'status' => PaymentStatus::Succeeded,
            'amount_minor' => 10500,
            'currency_code' => $currency->code,
            'confirmed_at' => now(),
        ]));

        return [$order, $payment, $plan, $price];
    }
}

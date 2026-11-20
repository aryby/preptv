<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SchemaConstraintsTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_schema_tables_are_created_without_replacing_framework_tables(): void
    {
        $tables = [
            'currencies',
            'plans',
            'plan_prices',
            'customer_profiles',
            'reseller_profiles',
            'reseller_customers',
            'reseller_packages',
            'reseller_wallets',
            'orders',
            'order_items',
            'wallet_transactions',
            'payments',
            'payment_webhook_events',
            'payment_refunds',
            'subscriptions',
            'subscription_histories',
            'invoices',
            'invoice_items',
            'credit_notes',
            'credit_note_items',
            'devices',
            'device_assignments',
            'streaming_sessions',
            'email_templates',
            'email_logs',
            'themes',
            'theme_settings',
            'settings',
            'users',
            'sessions',
            'roles',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Expected table [{$table}] to exist.");
        }
    }

    public function test_guest_order_keeps_its_snapshot_without_a_user_account(): void
    {
        DB::table('currencies')->insert([
            'code' => 'EUR',
            'name' => 'Euro',
            'minor_unit' => 2,
            'is_active' => true,
        ]);

        $orderId = DB::table('orders')->insertGetId([
            'public_id' => '01J00000000000000000000000',
            'currency_code' => 'EUR',
            'status' => 'pending_payment',
            'subtotal_minor' => 1200,
            'tax_minor' => 0,
            'total_minor' => 1200,
            'customer_snapshot' => json_encode(['name' => 'Guest Buyer', 'email' => 'guest@example.test']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'user_id' => null,
            'currency_code' => 'EUR',
            'total_minor' => 1200,
        ]);
        $this->assertSame(
            'guest@example.test',
            json_decode(DB::table('orders')->where('id', $orderId)->value('customer_snapshot'), true)['email']
        );
    }

    public function test_webhook_event_idempotency_is_enforced_per_provider(): void
    {
        DB::table('currencies')->insert([
            'code' => 'USD',
            'name' => 'US Dollar',
            'minor_unit' => 2,
            'is_active' => true,
        ]);
        $userId = DB::table('users')->insertGetId([
            'name' => 'Buyer',
            'email' => 'buyer@example.test',
            'password' => 'hashed-password',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $orderId = DB::table('orders')->insertGetId([
            'public_id' => '01J00000000000000000000001',
            'user_id' => $userId,
            'currency_code' => 'USD',
            'status' => 'pending_payment',
            'subtotal_minor' => 500,
            'tax_minor' => 0,
            'total_minor' => 500,
            'customer_snapshot' => json_encode(['name' => 'Buyer', 'email' => 'buyer@example.test']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $paymentId = DB::table('payments')->insertGetId([
            'order_id' => $orderId,
            'provider' => 'test-gateway',
            'idempotency_key' => 'order-payment-1',
            'status' => 'pending',
            'amount_minor' => 500,
            'currency_code' => 'USD',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $event = [
            'provider' => 'test-gateway',
            'provider_event_id' => 'evt-1',
            'payment_id' => $paymentId,
            'payload_hash' => str_repeat('a', 64),
            'status' => 'received',
            'attempt_count' => 0,
            'received_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('payment_webhook_events')->insert($event);

        $this->expectException(QueryException::class);
        DB::table('payment_webhook_events')->insert($event);
    }

    public function test_financial_and_subscription_history_rows_restrict_parent_deletion(): void
    {
        DB::table('currencies')->insert([
            'code' => 'GBP',
            'name' => 'Pound Sterling',
            'minor_unit' => 2,
            'is_active' => true,
        ]);
        $planId = DB::table('plans')->insertGetId([
            'slug' => 'monthly',
            'name' => 'Monthly',
            'status' => 'active',
            'device_limit' => 2,
            'concurrent_session_limit' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $priceId = DB::table('plan_prices')->insertGetId([
            'plan_id' => $planId,
            'currency_code' => 'GBP',
            'billing_period_unit' => 'month',
            'billing_period_count' => 1,
            'amount_minor' => 1000,
            'effective_from' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $orderId = DB::table('orders')->insertGetId([
            'public_id' => '01J00000000000000000000002',
            'currency_code' => 'GBP',
            'status' => 'paid',
            'subtotal_minor' => 1000,
            'tax_minor' => 0,
            'total_minor' => 1000,
            'customer_snapshot' => json_encode(['name' => 'Guest']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('order_items')->insert([
            'order_id' => $orderId,
            'line_number' => 1,
            'plan_id' => $planId,
            'plan_price_id' => $priceId,
            'currency_code' => 'GBP',
            'plan_name_snapshot' => 'Monthly',
            'billing_period_unit' => 'month',
            'billing_period_count' => 1,
            'quantity' => 1,
            'unit_amount_minor' => 1000,
            'subtotal_minor' => 1000,
            'tax_rate_basis_points' => 0,
            'tax_minor' => 0,
            'total_minor' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            DB::table('plans')->where('id', $planId)->delete();
            $this->fail('A plan referenced by a financial snapshot must not be deleted.');
        } catch (QueryException) {
            $this->assertDatabaseHas('plans', ['id' => $planId]);
        }

        try {
            DB::table('orders')->where('id', $orderId)->delete();
            $this->fail('An order with line items must not be deleted.');
        } catch (QueryException) {
            $this->assertDatabaseHas('orders', ['id' => $orderId]);
        }

        try {
            DB::table('currencies')->where('code', 'GBP')->delete();
            $this->fail('A currency referenced by financial records must not be deleted.');
        } catch (QueryException) {
            $this->assertDatabaseHas('currencies', ['code' => 'GBP']);
        }
    }

    public function test_wallet_transactions_match_wallet_currency_and_are_idempotent(): void
    {
        DB::table('currencies')->insert([
            ['code' => 'CAD', 'name' => 'Canadian Dollar', 'minor_unit' => 2, 'is_active' => true],
            ['code' => 'USD', 'name' => 'US Dollar', 'minor_unit' => 2, 'is_active' => true],
        ]);
        $userId = DB::table('users')->insertGetId([
            'name' => 'Reseller',
            'email' => 'reseller@example.test',
            'password' => 'hashed-password',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $resellerId = DB::table('reseller_profiles')->insertGetId([
            'user_id' => $userId,
            'business_name' => 'Example Reseller',
            'status' => 'active',
            'commission_basis_points' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $walletId = DB::table('reseller_wallets')->insertGetId([
            'reseller_profile_id' => $resellerId,
            'currency_code' => 'CAD',
            'balance_minor' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $transaction = [
            'reseller_wallet_id' => $walletId,
            'currency_code' => 'CAD',
            'amount_minor' => 250,
            'balance_after_minor' => 250,
            'type' => 'credit',
            'idempotency_key' => 'credit-1',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        try {
            DB::table('wallet_transactions')->insert(array_merge($transaction, ['currency_code' => 'USD']));
            $this->fail('A wallet transaction must use the wallet currency.');
        } catch (QueryException) {
            $this->assertDatabaseCount('wallet_transactions', 0);
        }

        DB::table('wallet_transactions')->insert($transaction);

        try {
            DB::table('wallet_transactions')->insert($transaction);
            $this->fail('A wallet idempotency key must not be reused.');
        } catch (QueryException) {
            $this->assertDatabaseCount('wallet_transactions', 1);
        }
    }

    public function test_deleting_a_user_preserves_order_identity_snapshot(): void
    {
        DB::table('currencies')->insert([
            'code' => 'AUD',
            'name' => 'Australian Dollar',
            'minor_unit' => 2,
            'is_active' => true,
        ]);
        $userId = DB::table('users')->insertGetId([
            'name' => 'Customer',
            'email' => 'customer@example.test',
            'password' => 'hashed-password',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $orderId = DB::table('orders')->insertGetId([
            'public_id' => '01J00000000000000000000003',
            'user_id' => $userId,
            'currency_code' => 'AUD',
            'status' => 'paid',
            'subtotal_minor' => 800,
            'tax_minor' => 0,
            'total_minor' => 800,
            'customer_snapshot' => json_encode(['name' => 'Customer', 'email' => 'customer@example.test']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->where('id', $userId)->delete();

        $order = DB::table('orders')->where('id', $orderId)->first();
        $this->assertNotNull($order);
        $this->assertNull($order->user_id);
        $this->assertSame(
            'customer@example.test',
            json_decode($order->customer_snapshot, true)['email']
        );
    }
}

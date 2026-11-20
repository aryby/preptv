<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reseller_profile_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('reseller_customer_id')->nullable()->constrained()->restrictOnDelete();
            $table->char('currency_code', 3);
            $table->foreign('currency_code')->references('code')->on('currencies')->restrictOnDelete();
            $table->string('status')->default('pending_payment');
            $table->unsignedBigInteger('subtotal_minor');
            $table->unsignedBigInteger('tax_minor')->default(0);
            $table->unsignedBigInteger('total_minor');
            $table->json('customer_snapshot');
            $table->json('seller_snapshot')->nullable();
            $table->char('guest_access_token_hash', 64)->nullable()->unique();
            $table->timestamp('guest_access_expires_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->index(['user_id', 'created_at'], 'orders_user_created_index');
            $table->index(['reseller_profile_id', 'status', 'created_at'], 'orders_reseller_status_index');
            $table->index(['status', 'created_at'], 'orders_status_created_index');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('plan_price_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reseller_profile_id')->nullable()->constrained()->restrictOnDelete();
            $table->char('currency_code', 3);
            $table->foreign('currency_code')->references('code')->on('currencies')->restrictOnDelete();
            $table->unsignedBigInteger('amount_minor');
            $table->string('billing_period_unit');
            $table->unsignedSmallInteger('billing_period_count');
            $table->unsignedSmallInteger('device_limit_snapshot');
            $table->unsignedSmallInteger('concurrent_session_limit_snapshot');
            $table->string('status')->default('pending');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('current_period_ends_at')->nullable();
            $table->timestamp('next_renewal_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->index(['status', 'next_renewal_at'], 'subscriptions_renewal_index');
            $table->index(['user_id', 'status'], 'subscriptions_user_status_index');
            $table->index(['reseller_profile_id', 'status'], 'subscriptions_reseller_status_index');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};

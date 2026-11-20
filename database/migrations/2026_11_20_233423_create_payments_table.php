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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->string('provider');
            $table->string('provider_payment_id')->nullable();
            $table->string('idempotency_key');
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency_code', 3);
            $table->foreign('currency_code')->references('code')->on('currencies')->restrictOnDelete();
            $table->string('failure_code')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->unique(['provider', 'idempotency_key'], 'payment_idempotency_unique');
            $table->unique(['provider', 'provider_payment_id'], 'payment_provider_id_unique');
            $table->index(['order_id', 'status', 'created_at'], 'payments_order_status_index');
            $table->index(['provider', 'status'], 'payments_provider_status_index');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

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
        Schema::create('payment_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->string('provider_refund_id')->nullable();
            $table->string('idempotency_key');
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency_code', 3);
            $table->foreign('currency_code')->references('code')->on('currencies')->restrictOnDelete();
            $table->string('reason_code')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unique(['payment_id', 'idempotency_key'], 'refund_idempotency_unique');
            $table->unique(['payment_id', 'provider_refund_id'], 'refund_provider_id_unique');
            $table->index(['payment_id', 'status'], 'refund_payment_status_index');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_refunds');
    }
};

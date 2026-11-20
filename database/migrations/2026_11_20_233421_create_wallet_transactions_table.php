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
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_wallet_id');
            $table->char('currency_code', 3);
            $table->foreign(['reseller_wallet_id', 'currency_code'])
                ->references(['id', 'currency_code'])
                ->on('reseller_wallets')
                ->restrictOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->restrictOnDelete();
            $table->bigInteger('amount_minor');
            $table->unsignedBigInteger('balance_after_minor');
            $table->string('type');
            $table->string('idempotency_key');
            $table->string('description')->nullable();
            $table->unique(['reseller_wallet_id', 'idempotency_key'], 'wallet_idempotency_unique');
            $table->index(['reseller_wallet_id', 'created_at'], 'wallet_transactions_created_index');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};

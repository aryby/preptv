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
        Schema::create('reseller_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_profile_id')->constrained()->restrictOnDelete();
            $table->char('currency_code', 3);
            $table->foreign('currency_code')->references('code')->on('currencies')->restrictOnDelete();
            $table->unsignedBigInteger('balance_minor')->default(0);
            $table->unique(['reseller_profile_id', 'currency_code'], 'reseller_wallet_currency_unique');
            $table->unique(['id', 'currency_code'], 'wallet_id_currency_unique');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reseller_wallets');
    }
};

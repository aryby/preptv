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
        Schema::create('plan_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->char('currency_code', 3);
            $table->foreign('currency_code')->references('code')->on('currencies')->restrictOnDelete();
            $table->string('billing_period_unit');
            $table->unsignedSmallInteger('billing_period_count');
            $table->unsignedBigInteger('amount_minor');
            $table->timestamp('effective_from');
            $table->timestamp('effective_until')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->unique(
                ['plan_id', 'currency_code', 'billing_period_unit', 'billing_period_count', 'effective_from'],
                'plan_price_version_unique'
            );
            $table->index(['currency_code', 'effective_from', 'effective_until'], 'plan_price_currency_dates_index');
            $table->index(['plan_id', 'archived_at'], 'plan_price_plan_archive_index');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_prices');
    }
};

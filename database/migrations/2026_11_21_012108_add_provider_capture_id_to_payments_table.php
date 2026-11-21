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
        Schema::table('payments', function (Blueprint $table) {
            $table->string('provider_capture_id')->nullable()->after('provider_payment_id');
            $table->unique(['provider', 'provider_capture_id'], 'payment_provider_capture_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique('payment_provider_capture_unique');
            $table->dropColumn('provider_capture_id');
        });
    }
};

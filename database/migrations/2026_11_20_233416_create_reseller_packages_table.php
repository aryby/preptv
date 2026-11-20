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
        Schema::create('reseller_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_profile_id')->constrained()->restrictOnDelete();
            $table->foreignId('plan_price_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('override_amount_minor')->nullable();
            $table->unsignedSmallInteger('commission_basis_points')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('effective_from');
            $table->timestamp('effective_until')->nullable();
            $table->unique(
                ['reseller_profile_id', 'plan_price_id', 'effective_from'],
                'reseller_package_version_unique'
            );
            $table->index(['reseller_profile_id', 'status', 'effective_until'], 'reseller_package_status_index');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reseller_packages');
    }
};

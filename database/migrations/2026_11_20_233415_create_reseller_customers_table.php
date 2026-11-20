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
        Schema::create('reseller_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_profile_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('archived_at')->nullable();
            $table->unique(['reseller_profile_id', 'customer_user_id'], 'reseller_customer_unique');
            $table->index(['reseller_profile_id', 'archived_at'], 'reseller_customer_archive_index');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reseller_customers');
    }
};

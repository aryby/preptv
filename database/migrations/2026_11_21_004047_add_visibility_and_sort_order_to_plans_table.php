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
        Schema::table('plans', function (Blueprint $table) {
            $table->boolean('is_public')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->index(['status', 'is_public', 'sort_order'], 'plans_public_catalog_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropIndex('plans_public_catalog_index');
            $table->dropColumn(['is_public', 'sort_order']);
        });
    }
};

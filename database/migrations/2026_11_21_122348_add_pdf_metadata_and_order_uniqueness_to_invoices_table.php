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
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('pdf_path')->nullable();
            $table->char('pdf_sha256', 64)->nullable();
            $table->timestamp('pdf_generated_at')->nullable();
            $table->timestamp('email_sent_at')->nullable();
            $table->unique('order_id', 'invoices_order_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_order_unique');
            $table->dropColumn([
                'pdf_path',
                'pdf_sha256',
                'pdf_generated_at',
                'email_sent_at',
            ]);
        });
    }
};

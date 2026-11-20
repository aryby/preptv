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
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_template_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('template_key_snapshot');
            $table->string('recipient_email');
            $table->string('status')->default('queued');
            $table->string('provider_message_id')->nullable();
            $table->string('error_code')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->index(['status', 'created_at'], 'email_logs_status_created_index');
            $table->index(['user_id', 'created_at'], 'email_logs_user_created_index');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};

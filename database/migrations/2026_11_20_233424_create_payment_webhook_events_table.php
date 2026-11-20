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
        Schema::create('payment_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('provider_event_id');
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->char('payload_hash', 64);
            $table->json('sanitized_payload')->nullable();
            $table->string('status')->default('received');
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->string('failure_code')->nullable();
            $table->unique(['provider', 'provider_event_id'], 'payment_webhook_event_unique');
            $table->index(['status', 'received_at'], 'payment_webhook_status_index');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_events');
    }
};

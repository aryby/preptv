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
        Schema::create('streaming_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->restrictOnDelete();
            $table->foreignId('device_assignment_id')->constrained()->restrictOnDelete();
            $table->string('provider_session_id')->nullable()->unique();
            $table->string('status')->default('active');
            $table->timestamp('started_at');
            $table->timestamp('heartbeat_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->index(['subscription_id', 'status', 'heartbeat_at'], 'streaming_sessions_active_index');
            $table->index(['device_assignment_id', 'ended_at'], 'streaming_sessions_assignment_end_index');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('streaming_sessions');
    }
};

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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('fingerprint_hash')->unique();
            $table->string('label')->nullable();
            $table->string('device_type')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->index(['owner_user_id', 'archived_at'], 'devices_owner_archive_index');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};

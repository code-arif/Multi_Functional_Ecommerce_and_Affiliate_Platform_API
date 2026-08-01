<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_name', 100)->nullable();
            $table->string('device_type', 50)->nullable(); // mobile, desktop, tablet
            $table->string('platform', 50)->nullable(); // ios, android, windows, mac
            $table->string('browser', 50)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('push_token')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->boolean('is_trusted')->default(false);
            $table->timestamps();

            $table->index('user_id');
            $table->index('last_active_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};

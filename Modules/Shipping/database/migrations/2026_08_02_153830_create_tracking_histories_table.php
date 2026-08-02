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
        Schema::create('tracking_histories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('status', 50);
            $table->string('location', 255)->nullable();
            $table->text('description')->nullable();
            $table->timestamp('tracked_at')->useCurrent();
            $table->timestamps();

            $table->index(['shipment_id', 'tracked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracking_histories');
    }
};

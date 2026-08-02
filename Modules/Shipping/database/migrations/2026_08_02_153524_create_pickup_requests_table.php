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
        Schema::create('pickup_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('courier_id')->constrained()->cascadeOnDelete();
            $table->string('status', 50)->default('pending');
            $table->date('pickup_date');
            $table->time('pickup_time_from');
            $table->time('pickup_time_to');
            $table->string('address', 500);
            $table->string('contact_name', 100);
            $table->string('contact_phone', 30);
            $table->text('notes')->nullable();
            $table->json('parcels')->nullable();
            $table->string('reference_code', 100)->nullable()->unique();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pickup_requests');
    }
};

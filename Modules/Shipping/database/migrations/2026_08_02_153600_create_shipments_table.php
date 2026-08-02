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
         Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('courier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shipping_rate_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tracking_number', 100)->nullable()->index();
            $table->string('carrier_tracking_code', 100)->nullable();
            $table->string('status', 50)->default('pending');
            $table->string('method', 50)->default('standard');
            $table->decimal('weight', 10, 2)->nullable();
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->string('sender_name', 100)->nullable();
            $table->string('sender_phone', 30)->nullable();
            $table->text('sender_address')->nullable();
            $table->string('recipient_name', 100)->nullable();
            $table->string('recipient_phone', 30)->nullable();
            $table->text('recipient_address')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};

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
         Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('shipping_zone_id')->constrained()->cascadeOnDelete();
            $table->foreignId('courier_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('method', 50)->default('standard');
            $table->decimal('base_rate', 12, 2)->default(0);
            $table->decimal('rate_per_kg', 12, 2)->default(0);
            $table->decimal('rate_per_item', 12, 2)->default(0);
            $table->decimal('free_shipping_min', 12, 2)->nullable();
            $table->decimal('max_weight', 10, 2)->nullable();
            $table->integer('estimated_days_min')->nullable();
            $table->integer('estimated_days_max')->nullable();
            $table->json('conditions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['shipping_zone_id', 'courier_id', 'method'], 'unique_zone_courier_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
    }
};

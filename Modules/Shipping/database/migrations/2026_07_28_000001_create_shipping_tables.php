<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Couriers (delivery service providers) ────────────────
        Schema::create('couriers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->string('display_name', 150)->nullable();
            $table->text('description')->nullable();
            $table->string('website', 255)->nullable();
            $table->string('tracking_url_template', 500)->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->string('contact_email', 100)->nullable();
            $table->json('supported_services')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // ─── Shipping Zones (geographic areas) ────────────────────
        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->text('description')->nullable();
            $table->json('countries')->nullable();
            $table->json('states')->nullable();
            $table->json('cities')->nullable();
            $table->json('postal_codes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // ─── Shipping Rates (pricing per zone/courier) ────────────
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
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

        // ─── Shipments (order shipments) ──────────────────────────
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
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

        // ─── Tracking Histories ───────────────────────────────────
        Schema::create('tracking_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('status', 50);
            $table->string('location', 255)->nullable();
            $table->text('description')->nullable();
            $table->timestamp('tracked_at')->useCurrent();
            $table->timestamps();

            $table->index(['shipment_id', 'tracked_at']);
        });

        // ─── Pickup Requests ──────────────────────────────────────
        Schema::create('pickup_requests', function (Blueprint $table) {
            $table->id();
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

    public function down(): void
    {
        Schema::dropIfExists('pickup_requests');
        Schema::dropIfExists('tracking_histories');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('shipping_rates');
        Schema::dropIfExists('shipping_zones');
        Schema::dropIfExists('couriers');
    }
};

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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();   // ORD-2024-000001
            $table->foreignId('user_id')
                ->nullable()                          // null = guest order
                ->constrained('users')
                ->nullOnDelete();

            // Order status
            $table->enum('status', [
                'pending',
                'confirmed',
                'processing',
                'shipped',
                'delivered',
                'cancelled',
                'refunded',
            ])->default('pending');

            // Pricing
            $table->decimal('subtotal', 12, 2);
            $table->decimal('shipping_charge', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);

            // Coupon
            $table->foreignId('coupon_id')
                ->nullable()
                ->constrained('coupons')
                ->nullOnDelete();
            $table->string('coupon_code')->nullable();

            // Shipping address (snapshot at order time)
            $table->string('shipping_name');
            $table->string('shipping_phone', 20);
            $table->string('shipping_email')->nullable();
            $table->text('shipping_address_line1');
            $table->text('shipping_address_line2')->nullable();
            $table->string('shipping_city');
            $table->string('shipping_state')->nullable();
            $table->string('shipping_postal_code', 20)->nullable();
            $table->string('shipping_country')->default('Bangladesh');

            // Payment
            $table->enum('payment_method', [
                'cod',
                'bkash',
                'nagad',
                'sslcommerz',
                'card'
            ])->default('cod');
            $table->enum('payment_status', [
                'pending',
                'paid',
                'failed',
                'refunded'
            ])->default('pending');

            // Notes
            $table->text('customer_note')->nullable();
            $table->text('admin_note')->nullable();

            // Tracking
            $table->string('tracking_number')->nullable();
            $table->string('shipping_carrier')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Guest identification
            $table->string('guest_email')->nullable();
            $table->string('guest_token')->nullable();  // for tracking without account

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('order_number');
            $table->index('user_id');
            $table->index('status');
            $table->index('payment_status');
            $table->index('created_at');
            $table->index('guest_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

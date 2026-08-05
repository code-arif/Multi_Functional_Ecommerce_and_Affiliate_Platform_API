<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * One customer checkout produces N vendor sub-orders (split by vendor).
     * Each row in `orders` belongs to exactly one vendor (or null = platform/general).
     * All sub-orders from a single checkout share the same `group_id`.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('order_number', 40)->unique();          // ORD-2026-000001
            $table->uuid('group_id')->nullable()->index();         // shared by vendor sub-orders of one checkout
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            // vendor_id is app-level scoped (no FK — the vendors table is created later in the migration chain)
            $table->unsignedBigInteger('vendor_id')->nullable()->index();

            // Order status
            $table->enum('status', ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'])
                ->default('pending')
                ->index();

            // Pricing (all money stored as decimal, never float)
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('shipping_charge', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('coupon_discount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);

            // Coupon
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            $table->string('coupon_code')->nullable();

            // Payment
            $table->enum('payment_method', ['cod', 'bkash', 'nagad', 'sslcommerz', 'card'])->default('cod');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending')->index();
            $table->string('shipping_method', 50)->nullable();

            // Shipping address (structured snapshot at order time)
            $table->string('shipping_name', 100);
            $table->string('shipping_phone', 20);
            $table->string('shipping_email')->nullable();
            $table->text('shipping_address_line1');
            $table->text('shipping_address_line2')->nullable();
            $table->string('shipping_city', 100);
            $table->string('shipping_state', 100)->nullable();
            $table->string('shipping_postal_code', 20)->nullable();
            $table->string('shipping_country', 100)->default('Bangladesh');

            // Address blobs (JSON, kept for backward compatibility with checkout payloads)
            $table->text('shipping_address')->nullable();
            $table->text('billing_address')->nullable();

            // Notes
            $table->text('customer_note')->nullable();
            $table->text('admin_note')->nullable();

            // Tracking
            $table->string('tracking_number')->nullable();
            $table->string('shipping_carrier')->nullable();
            $table->string('tracking_token', 64)->nullable()->unique();

            // Guest identification
            $table->string('guest_email')->nullable();
            $table->string('guest_token', 64)->nullable();

            $table->text('cancel_reason')->nullable();

            // Status timestamps
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['vendor_id', 'status']);
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

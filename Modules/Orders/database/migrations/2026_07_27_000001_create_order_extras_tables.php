<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Add model-expected columns to orders table ─────────────
        if (Schema::hasTable('orders') && !Schema::hasColumn('orders', 'shipping_cost')) {
            Schema::table('orders', function (Blueprint $table) {
                // Model-compatible columns alongside existing migration columns
                $table->decimal('shipping_cost', 12, 2)->default(0)->after('subtotal');
                $table->decimal('coupon_discount', 12, 2)->default(0)->after('discount_amount');
                $table->string('shipping_method', 50)->nullable()->after('payment_status');
                $table->text('shipping_address')->nullable()->after('shipping_method');
                $table->text('billing_address')->nullable()->after('shipping_address');
                $table->text('notes')->nullable()->after('billing_address');
                $table->string('tracking_token', 100)->nullable()->unique()->after('admin_note');
                $table->text('cancel_reason')->nullable()->after('cancelled_at');
                $table->timestamp('paid_at')->nullable()->after('cancelled_at');
                $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete()->after('user_id');
                $table->index('vendor_id');
            });
        }

        // ── Add model-expected columns to order_status_histories table ─
        if (Schema::hasTable('order_status_histories') && !Schema::hasColumn('order_status_histories', 'from_status')) {
            Schema::table('order_status_histories', function (Blueprint $table) {
                $table->string('from_status')->nullable()->after('order_id');
                $table->string('to_status')->nullable()->after('from_status');
                $table->text('notes')->nullable()->after('to_status');
                $table->foreignId('changed_by')->nullable()->after('notes');
                $table->string('changed_by_name', 100)->nullable()->after('changed_by');
            });
        }

        // ── Add model-expected columns to order_items table ────────
        if (Schema::hasTable('order_items') && !Schema::hasColumn('order_items', 'total_price')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->decimal('total_price', 12, 2)->default(0)->after('unit_price');
                $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete()->after('product_id');
                $table->string('product_name')->nullable()->change();
                $table->string('product_sku')->nullable()->change();
                $table->string('product_image')->nullable()->after('product_sku');
            });
        }

        // ── Invoices table ─────────────────────────────────────────
        if (!Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
                $table->string('invoice_number', 50)->unique();
                $table->decimal('subtotal', 12, 2);
                $table->decimal('shipping_cost', 12, 2)->default(0);
                $table->decimal('discount_amount', 12, 2)->default(0);
                $table->decimal('tax_amount', 12, 2)->default(0);
                $table->decimal('total', 12, 2);
                $table->decimal('paid_amount', 12, 2)->default(0);
                $table->decimal('due_amount', 12, 2)->default(0);
                $table->enum('status', ['pending', 'paid', 'partially_paid', 'overdue', 'cancelled'])->default('pending');
                $table->timestamp('issued_at')->nullable();
                $table->timestamp('due_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index('order_id');
                $table->index('status');
            });
        }

        // ── Cancel Requests table ──────────────────────────────────
        if (!Schema::hasTable('cancel_requests')) {
            Schema::create('cancel_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('reason');
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->text('admin_response')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();

                $table->index('status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cancel_requests');
        Schema::dropIfExists('invoices');

        if (Schema::hasColumn('orders', 'shipping_cost')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn([
                    'shipping_cost', 'coupon_discount', 'shipping_method', 'shipping_address',
                    'billing_address', 'notes', 'tracking_token', 'cancel_reason',
                    'paid_at', 'vendor_id',
                ]);
            });
        }

        if (Schema::hasColumn('order_items', 'total_price')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropColumn(['total_price', 'vendor_id', 'product_image']);
            });
        }
    }
};

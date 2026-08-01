<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create promotions table
        if (!Schema::hasTable('promotions')) {
            Schema::create('promotions', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->enum('type', [
                    'flash_sale',
                    'buy_x_get_y',
                    'tiered_discount',
                    'seasonal',
                    'coupon',
                    'free_shipping',
                ])->default('flash_sale');
                $table->enum('discount_type', ['percentage', 'fixed'])->default('percentage');
                $table->decimal('discount_value', 10, 2)->default(0);
                $table->decimal('maximum_discount', 12, 2)->nullable();

                // Buy X Get Y fields
                $table->integer('min_quantity')->nullable();
                $table->integer('free_quantity')->nullable();
                $table->enum('discount_on', ['cheapest', 'all'])->default('cheapest');

                // Tiered discount tiers (JSON: [{"from":2,"value":10}, {"from":5,"value":15}])
                $table->json('tiers')->nullable();

                // Scope: what this promotion applies to
                $table->enum('applies_to', ['all', 'products', 'categories', 'vendors'])
                    ->default('all');
                $table->json('product_ids')->nullable();
                $table->json('category_ids')->nullable();
                $table->json('vendor_ids')->nullable();

                // Usage
                $table->integer('usage_limit')->nullable();
                $table->integer('usage_per_user')->default(1);
                $table->integer('used_count')->default(0);
                $table->boolean('is_active')->default(true);

                // Scheduling
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();

                // Display
                $table->string('badge_text')->nullable();
                $table->string('badge_color')->default('#ff4444');
                $table->integer('sort_order')->default(0);

                $table->timestamps();
                $table->softDeletes();

                $table->index('type');
                $table->index('is_active');
                $table->index(['starts_at', 'ends_at']);
                $table->index('sort_order');
            });
        }

        // Add promotion_id to coupons table
        if (Schema::hasTable('coupons') && !Schema::hasColumn('coupons', 'promotion_id')) {
            Schema::table('coupons', function (Blueprint $table) {
                $table->foreignId('promotion_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('promotions')
                    ->nullOnDelete();
            });
        }

        // Add applied_promotion_id to order_items table
        if (Schema::hasTable('order_items') && !Schema::hasColumn('order_items', 'applied_promotion_id')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->foreignId('applied_promotion_id')
                    ->nullable()
                    ->after('subtotal')
                    ->constrained('promotions')
                    ->nullOnDelete();
                $table->decimal('promotion_discount', 12, 2)
                    ->default(0)
                    ->after('applied_promotion_id');
            });
        }

        // Create promotion_usages table
        if (!Schema::hasTable('promotion_usages')) {
            Schema::create('promotion_usages', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('promotion_id')
                    ->constrained('promotions')
                    ->cascadeOnDelete();
                $table->foreignId('user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->foreignId('order_id')
                    ->nullable()
                    ->constrained('orders')
                    ->nullOnDelete();
                $table->decimal('discount_amount', 12, 2);
                $table->timestamps();

                $table->index('promotion_id');
                $table->index('user_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_usages');

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['applied_promotion_id', 'promotion_discount']);
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn('promotion_id');
        });

        Schema::dropIfExists('promotions');
    }
};

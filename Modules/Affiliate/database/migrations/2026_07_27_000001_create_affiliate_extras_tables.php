<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add missing columns to affiliate_products (model expects these)
        if (Schema::hasTable('affiliate_products')) {
            Schema::table('affiliate_products', function (Blueprint $table) {
                if (!Schema::hasColumn('affiliate_products', 'commission_type')) {
                    $table->enum('commission_type', ['percentage', 'fixed'])
                        ->default('percentage')->after('affiliate_link');
                }
                if (!Schema::hasColumn('affiliate_products', 'commission_value')) {
                    $table->decimal('commission_value', 10, 2)->default(0)
                        ->after('commission_type');
                }
                if (!Schema::hasColumn('affiliate_products', 'is_featured')) {
                    $table->boolean('is_featured')->default(false)
                        ->after('click_count');
                }
                if (!Schema::hasColumn('affiliate_products', 'sort_order')) {
                    $table->integer('sort_order')->default(0)
                        ->after('is_featured');
                }
            });
        }

        // Create affiliate_conversions table
        if (!Schema::hasTable('affiliate_conversions')) {
            Schema::create('affiliate_conversions', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('affiliate_product_id')
                    ->constrained('affiliate_products')
                    ->cascadeOnDelete();
                $table->foreignId('user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->foreignId('order_id')
                    ->nullable()
                    ->constrained('orders')
                    ->nullOnDelete();
                $table->decimal('order_amount', 12, 2)->default(0);
                $table->decimal('commission_amount', 12, 2)->default(0);
                $table->enum('status', ['pending', 'approved', 'rejected'])
                    ->default('pending');
                $table->string('ip_address', 45)->nullable();
                $table->string('referrer')->nullable();
                $table->timestamp('converted_at');
                $table->timestamps();

                $table->index('affiliate_product_id');
                $table->index('user_id');
                $table->index('status');
            });
        }

        // Create affiliate_earnings table
        if (!Schema::hasTable('affiliate_earnings')) {
            Schema::create('affiliate_earnings', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();
                $table->foreignId('affiliate_product_id')
                    ->nullable()
                    ->constrained('affiliate_products')
                    ->nullOnDelete();
                $table->foreignId('order_id')
                    ->nullable()
                    ->constrained('orders')
                    ->nullOnDelete();
                $table->decimal('amount', 12, 2)->default(0);
                $table->enum('type', ['commission', 'bonus', 'adjustment'])
                    ->default('commission');
                $table->enum('status', ['pending', 'available', 'paid', 'cancelled'])
                    ->default('pending');
                $table->text('notes')->nullable();
                $table->timestamp('available_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();

                $table->index('user_id');
                $table->index('status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_earnings');
        Schema::dropIfExists('affiliate_conversions');

        Schema::table('affiliate_products', function (Blueprint $table) {
            $table->dropColumn(['commission_type', 'commission_value', 'is_featured', 'sort_order']);
        });
    }
};

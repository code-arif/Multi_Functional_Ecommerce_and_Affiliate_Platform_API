<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Categories ─────────────────────────────────────────────
        if (!Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('image')->nullable();
                $table->string('banner')->nullable();
                $table->string('icon')->nullable();
                $table->string('meta_title')->nullable();
                $table->text('meta_description')->nullable();
                $table->string('meta_keywords')->nullable();
                $table->boolean('is_featured')->default(false);
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();
                $table->index(['slug', 'is_active', 'parent_id', 'sort_order']);
            });
        }

        // ── Brands ─────────────────────────────────────────────────
        if (!Schema::hasTable('brands')) {
            Schema::create('brands', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('logo')->nullable();
                $table->string('website')->nullable();
                $table->string('meta_title')->nullable();
                $table->text('meta_description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();
                $table->index(['slug', 'is_active']);
            });
        }

        // ── Products ───────────────────────────────────────────────
        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
                $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('sku')->unique()->nullable();
                $table->enum('type', ['simple', 'variable', 'affiliate'])->default('simple');
                $table->decimal('price', 12, 2)->default(0);
                $table->decimal('sale_price', 12, 2)->nullable();
                $table->decimal('cost_price', 12, 2)->nullable();
                $table->integer('stock_quantity')->default(0);
                $table->integer('low_stock_threshold')->default(5);
                $table->boolean('manage_stock')->default(true);
                $table->enum('stock_status', ['in_stock', 'out_of_stock', 'on_backorder'])->default('in_stock');
                $table->text('short_description')->nullable();
                $table->longText('description')->nullable();
                $table->string('thumbnail')->nullable();
                $table->decimal('average_rating', 3, 2)->default(0);
                $table->integer('total_reviews')->default(0);
                $table->integer('total_sold')->default(0);
                $table->decimal('weight', 8, 2)->nullable();
                $table->string('weight_unit')->default('kg');
                $table->json('tags')->nullable();
                $table->boolean('is_featured')->default(false);
                $table->boolean('is_new')->default(false);
                $table->boolean('is_bestseller')->default(false);
                $table->string('meta_title')->nullable();
                $table->text('meta_description')->nullable();
                $table->string('meta_keywords')->nullable();
                // Product approval workflow: pending = awaiting admin review, draft = vendor working on it
                $table->enum('status', ['pending', 'draft', 'active', 'inactive'])->default('draft');
                $table->timestamp('published_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['slug', 'category_id', 'brand_id', 'status', 'is_featured', 'price', 'average_rating', 'total_sold', 'created_at']);
            });
        }

        // ── Product Images ─────────────────────────────────────────
        if (!Schema::hasTable('product_images')) {
            Schema::create('product_images', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->string('image_path');
                $table->string('alt_text')->nullable();
                $table->boolean('is_primary')->default(false);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
                $table->index(['product_id', 'is_primary']);
            });
        }

        // ── Product Attributes ─────────────────────────────────────
        if (!Schema::hasTable('product_attributes')) {
            Schema::create('product_attributes', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->string('name');
                $table->integer('sort_order')->default(0);
                $table->timestamps();
                $table->index('product_id');
            });
        }

        // ── Product Attribute Values ───────────────────────────────
        if (!Schema::hasTable('product_attribute_values')) {
            Schema::create('product_attribute_values', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('product_attribute_id')->constrained('product_attributes')->cascadeOnDelete();
                $table->string('value');
                $table->string('color_code')->nullable();
                $table->integer('sort_order')->default(0);
                $table->timestamps();
                $table->index('product_attribute_id');
            });
        }

        // ── Product Variants ───────────────────────────────────────
        if (!Schema::hasTable('product_variants')) {
            Schema::create('product_variants', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->string('sku')->nullable()->unique();
                $table->string('name')->nullable();
                $table->json('attributes');
                $table->decimal('price', 12, 2);
                $table->decimal('sale_price', 12, 2)->nullable();
                $table->integer('stock_quantity')->default(0);
                $table->decimal('weight', 8, 2)->nullable();
                $table->string('image')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
                $table->index(['product_id', 'sku', 'is_active']);
            });
        }

        // ── Vendor-Product Mapping (NEW - SRS Section 4.5) ─────────
        // Many vendors can sell one catalog product with different price/stock
        if (!Schema::hasTable('vendor_product_prices')) {
            Schema::create('vendor_product_prices', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->unsignedBigInteger('vendor_id');
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->decimal('price', 12, 2)->default(0);
                $table->decimal('sale_price', 12, 2)->nullable();
                $table->integer('stock_quantity')->default(0);
                $table->integer('low_stock_threshold')->default(5);
                $table->boolean('manage_stock')->default(true);
                $table->enum('stock_status', ['in_stock', 'out_of_stock', 'on_backorder'])->default('in_stock');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['vendor_id', 'product_id'], 'vendor_product_unique');
                $table->index(['vendor_id', 'product_id', 'is_active']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_product_prices');

        // Only drop if we created them (check if tables exist in the main migration)
        if (!Schema::hasTable('migrations') || !\DB::table('migrations')->where('migration', 'like', '%create_products_table%')->exists()) {
            Schema::dropIfExists('product_variants');
            Schema::dropIfExists('product_attribute_values');
            Schema::dropIfExists('product_attributes');
            Schema::dropIfExists('product_images');
            Schema::dropIfExists('products');
            Schema::dropIfExists('brands');
            Schema::dropIfExists('categories');
        }
    }
};

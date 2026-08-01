<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Recently Viewed ────────────────────────────────────────
        if (!Schema::hasTable('recent_views')) {
            Schema::create('recent_views', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->string('session_id', 100)->nullable()->index();
                $table->timestamps();

                $table->index(['user_id', 'product_id']);
                $table->index(['session_id', 'product_id']);
                $table->index('created_at');
            });
        }

        // ── Compare Lists ──────────────────────────────────────────
        if (!Schema::hasTable('compare_lists')) {
            Schema::create('compare_lists', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
                $table->string('session_id', 100)->nullable()->index();
                $table->timestamps();

                $table->index(['user_id']);
            });
        }

        // ── Compare List Items ─────────────────────────────────────
        if (!Schema::hasTable('compare_list_items')) {
            Schema::create('compare_list_items', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('compare_list_id')->constrained('compare_lists')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['compare_list_id', 'product_id'], 'compare_list_product_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('compare_list_items');
        Schema::dropIfExists('compare_lists');
        Schema::dropIfExists('recent_views');
    }
};

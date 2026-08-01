<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add columns to existing reviews table
        Schema::table('reviews', function (Blueprint $table) {
            if (!Schema::hasColumn('reviews', 'vendor_response')) {
                $table->text('vendor_response')->nullable()->after('status');
            }
            if (!Schema::hasColumn('reviews', 'vendor_responded_at')) {
                $table->timestamp('vendor_responded_at')->nullable()->after('vendor_response');
            }
            if (!Schema::hasColumn('reviews', 'helpful_count')) {
                $table->integer('helpful_count')->default(0)->after('vendor_responded_at');
            }
        });

        // Create review helpful votes table
        if (!Schema::hasTable('review_helpful_votes')) {
            Schema::create('review_helpful_votes', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('review_id')
                    ->constrained('reviews')
                    ->cascadeOnDelete();
                $table->foreignId('user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();
                $table->timestamps();

                // One vote per user per review
                $table->unique(['review_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('review_helpful_votes');

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn(['vendor_response', 'vendor_responded_at', 'helpful_count']);
        });
    }
};

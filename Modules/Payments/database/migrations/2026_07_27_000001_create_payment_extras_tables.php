<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Fix payment table: add model-expected columns ──────────
        if (Schema::hasTable('payments') && !Schema::hasColumn('payments', 'payment_method')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('payment_method', 50)->nullable()->after('gateway');
                $table->string('payment_status', 50)->nullable()->after('status');
            });
        }

        // ── Transactions ledger ────────────────────────────────────
        if (!Schema::hasTable('transactions')) {
            Schema::create('transactions', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
                $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
                $table->string('transaction_id', 100)->nullable()->unique();
                $table->enum('type', ['payment', 'refund', 'partial_refund', 'capture', 'void'])->default('payment');
                $table->decimal('amount', 15, 2);
                $table->decimal('fee', 15, 2)->default(0);
                $table->decimal('net', 15, 2)->default(0);
                $table->string('currency', 10)->default('BDT');
                $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('pending');
                $table->json('gateway_response')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['payment_id', 'type']);
                $table->index('transaction_id');
                $table->index('status');
                $table->index('created_at');
            });
        }

        // ── Refunds ────────────────────────────────────────────────
        if (!Schema::hasTable('refunds')) {
            Schema::create('refunds', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
                $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
                $table->string('refund_transaction_id', 100)->nullable();
                $table->decimal('amount', 15, 2);
                $table->decimal('fee', 15, 2)->default(0);
                $table->string('currency', 10)->default('BDT');
                $table->text('reason')->nullable();
                $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
                $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('processed_at')->nullable();
                $table->json('gateway_response')->nullable();
                $table->timestamps();

                $table->index('payment_id');
                $table->index('status');
            });
        }

        // ── Payment Methods (stored user payment methods) ──────────
        if (!Schema::hasTable('payment_methods')) {
            Schema::create('payment_methods', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('gateway', 50);            // stripe, bkash, etc.
                $table->string('gateway_method_id', 100); // stripe pm_xxx or account reference
                $table->string('type', 50)->nullable();   // card, mobile_banking
                $table->string('label', 100)->nullable(); // "Visa ending in 4242"
                $table->json('details')->nullable();      // masked card info, etc.
                $table->boolean('is_default')->default(false);
                $table->timestamps();

                $table->unique(['user_id', 'gateway_method_id']);
                $table->index(['user_id', 'is_default']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('transactions');

        if (Schema::hasColumn('payments', 'payment_method')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropColumn(['payment_method', 'payment_status']);
            });
        }
    }
};

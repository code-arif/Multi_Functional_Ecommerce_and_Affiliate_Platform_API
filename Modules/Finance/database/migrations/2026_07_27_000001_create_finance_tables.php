<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Commissions ────────────────────────────────────────────
        if (!Schema::hasTable('commissions')) {
            Schema::create('commissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
                $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
                $table->decimal('order_total', 15, 2);
                $table->decimal('commission_rate', 5, 2); // e.g., 10.00 = 10%
                $table->string('commission_type', 20)->default('percentage'); // percentage, fixed
                $table->decimal('commission_amount', 15, 2); // calculated amount
                $table->enum('status', ['pending', 'approved', 'cancelled'])->default('pending');
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['order_id', 'vendor_id'], 'commission_order_vendor_unique');
                $table->index(['vendor_id', 'status']);
                $table->index('status');
            });
        }

        // ── Vendor Payout Requests ─────────────────────────────────
        if (!Schema::hasTable('vendor_payout_requests')) {
            Schema::create('vendor_payout_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
                $table->decimal('amount', 15, 2);
                $table->decimal('balance_before', 15, 2);
                $table->decimal('balance_after', 15, 2);
                $table->string('payment_method', 50)->nullable();   // bank, bkash, etc.
                $table->text('payment_details')->nullable();         // account info snapshot
                $table->text('notes')->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected', 'completed', 'failed'])->default('pending');
                $table->text('admin_notes')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['vendor_id', 'status']);
                $table->index('status');
            });
        }

        // ── Vendor Settlements (periodic reports) ──────────────────
        if (!Schema::hasTable('vendor_settlements')) {
            Schema::create('vendor_settlements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
                $table->string('period_label', 50);         // "July 2026", "Q3 2026"
                $table->date('period_start');
                $table->date('period_end');
                $table->decimal('total_sales', 15, 2)->default(0);
                $table->decimal('total_commission', 15, 2)->default(0);
                $table->decimal('net_earnings', 15, 2)->default(0);    // sales - commission
                $table->decimal('total_paid', 15, 2)->default(0);       // paid out during period
                $table->decimal('balance_carried', 15, 2)->default(0);  // carried to next period
                $table->enum('status', ['draft', 'finalized', 'paid'])->default('draft');
                $table->timestamp('finalized_at')->nullable();
                $table->timestamps();

                $table->unique(['vendor_id', 'period_start', 'period_end'], 'settlement_period_unique');
                $table->index(['vendor_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_settlements');
        Schema::dropIfExists('vendor_payout_requests');
        Schema::dropIfExists('commissions');
    }
};

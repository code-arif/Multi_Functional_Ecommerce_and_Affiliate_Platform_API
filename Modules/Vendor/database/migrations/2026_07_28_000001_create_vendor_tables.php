<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Vendors ──────────────────────────────────────────────
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('shop_name', 200);
            $table->string('slug', 200)->unique();
            $table->string('email', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('banner')->nullable();
            $table->enum('status', ['pending', 'active', 'suspended', 'rejected'])->default('pending');
            $table->decimal('commission_rate', 5, 2)->default(0); // percentage
            $table->string('commission_type', 20)->default('percentage'); // fixed, percentage
            $table->decimal('wallet_balance', 15, 2)->default(0);
            $table->decimal('total_earned', 15, 2)->default(0);
            $table->decimal('total_withdrawn', 15, 2)->default(0);
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('user_id');
        });

        // ─── Vendor Profiles (extended shop info) ────────────────
        Schema::create('vendor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->string('business_type', 100)->nullable();
            $table->string('business_registration_number', 100)->nullable();
            $table->string('tax_id', 100)->nullable();
            $table->string('website')->nullable();
            $table->string('social_facebook')->nullable();
            $table->string('social_instagram')->nullable();
            $table->string('social_youtube')->nullable();
            $table->string('return_policy', 50)->default('7_days');
            $table->string('shipping_policy', 50)->default('standard');
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // ─── Vendor Addresses ────────────────────────────────────
        Schema::create('vendor_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->string('label', 50)->default('Business');
            $table->string('address_line_1', 255);
            $table->string('address_line_2', 255)->nullable();
            $table->string('city', 100);
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->default('Bangladesh');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_default')->default(true);
            $table->timestamps();
        });

        // ─── Vendor Bank Accounts ────────────────────────────────
        Schema::create('vendor_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->string('bank_name', 200);
            $table->string('branch_name', 200)->nullable();
            $table->string('account_name', 200);
            $table->string('account_number', 100);
            $table->string('routing_number', 50)->nullable();
            $table->string('swift_code', 20)->nullable();
            $table->string('mobile_banking_provider', 50)->nullable(); // bKash, Nagad, etc.
            $table->string('mobile_banking_number', 50)->nullable();
            $table->boolean('is_default')->default(true);
            $table->timestamps();
        });

        // ─── Vendor Documents (KYC) ──────────────────────────────
        Schema::create('vendor_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50); // trade_license, nid, bin, tin, passport
            $table->string('document_path');
            $table->string('document_number', 100)->nullable();
            $table->date('expiry_date')->nullable();
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['vendor_id', 'type']);
        });

        // ─── Vendor Staff ────────────────────────────────────────
        Schema::create('vendor_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 50)->default('staff'); // manager, staff
            $table->json('permissions')->nullable(); // fine-grained scoped permissions
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['vendor_id', 'user_id']);
            $table->index('vendor_id');
        });

        // ─── Vendor Wallet Transactions ──────────────────────────
        Schema::create('vendor_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50); // commission, payout, adjustment, refund
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_before', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->string('description')->nullable();
            $table->string('reference_type')->nullable(); // order, payout
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('status', 50)->default('completed'); // pending, completed, failed
            $table->timestamps();

            $table->index(['vendor_id', 'type']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_wallet_transactions');
        Schema::dropIfExists('vendor_staff');
        Schema::dropIfExists('vendor_documents');
        Schema::dropIfExists('vendor_bank_accounts');
        Schema::dropIfExists('vendor_addresses');
        Schema::dropIfExists('vendor_profiles');
        Schema::dropIfExists('vendors');
    }
};

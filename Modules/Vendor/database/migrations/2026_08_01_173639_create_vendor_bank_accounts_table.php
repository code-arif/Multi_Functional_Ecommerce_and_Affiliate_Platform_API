<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->string('bank_name', 200)->nullable();
            $table->string('branch_name', 200)->nullable();
            $table->string('account_name', 200)->nullable();
            $table->string('account_number', 100)->nullable();
            $table->string('routing_number', 50)->nullable();
            $table->string('swift_code', 50)->nullable();
            $table->string('mobile_banking_provider', 50)->nullable();
            $table->string('mobile_banking_number', 50)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index('is_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_bank_accounts');
    }
};

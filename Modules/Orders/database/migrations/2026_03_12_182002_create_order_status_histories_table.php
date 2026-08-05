<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Every status change is recorded here with the actor who performed it,
     * giving Super Admin a complete, immutable audit trail of the platform.
     */
    public function up(): void
    {
        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();

            $table->string('old_status')->nullable();
            $table->string('new_status');

            // Who triggered the change: customer / vendor / admin / system
            $table->enum('actor_type', ['customer', 'vendor', 'admin', 'system'])->default('system');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('changed_by_name', 100)->nullable();

            $table->text('notes')->nullable();
            $table->boolean('notify_customer')->default(true);

            $table->timestamps();

            $table->index(['order_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
    }
};

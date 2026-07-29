<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // vendor_id may already exist from the order_extras module migration
        // If the column exists, just add the foreign key constraint
        // If not, create the column with the constraint
        if (Schema::hasColumn('orders', 'vendor_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreign('vendor_id')
                    ->references('id')
                    ->on('vendors')
                    ->nullOnDelete();
            });
        } else {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreignId('vendor_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('vendors')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
            $table->dropColumn('vendor_id');
        });
    }
};

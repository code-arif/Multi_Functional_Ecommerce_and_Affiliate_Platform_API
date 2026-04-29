<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();

            $table->string('name', 100);      // Bangladeshi Taka
            $table->string('code', 10)->unique(); // BDT
            $table->string('symbol', 10);    // ৳

            $table->decimal('exchange_rate', 18, 8)->default(1);

            $table->tinyInteger('precision')->default(2);

            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};

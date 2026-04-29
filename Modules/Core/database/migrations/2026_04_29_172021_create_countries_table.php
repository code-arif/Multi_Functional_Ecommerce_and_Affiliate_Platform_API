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
        Schema::create('countries', function (Blueprint $table) {
            $table->id();

            $table->string('name', 120);
            $table->string('iso2', 2)->unique();      // BD
            $table->string('iso3', 3)->unique();      // BGD
            $table->string('phone_code', 10)->nullable(); // +880

            $table->string('currency_code', 10)->nullable(); // BDT
            $table->string('currency_symbol', 10)->nullable(); // ৳

            $table->string('flag')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};

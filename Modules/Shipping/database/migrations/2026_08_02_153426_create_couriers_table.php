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
        Schema::create('couriers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->string('display_name', 150)->nullable();
            $table->text('description')->nullable();
            $table->string('website', 255)->nullable();
            $table->string('tracking_url_template', 500)->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->string('contact_email', 100)->nullable();
            $table->json('supported_services')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('couriers');
    }
};

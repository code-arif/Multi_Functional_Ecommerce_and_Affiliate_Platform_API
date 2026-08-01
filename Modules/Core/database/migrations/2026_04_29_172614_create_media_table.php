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
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('disk')->default('public'); // s3/local
            $table->string('directory')->nullable();

            $table->string('file_name');
            $table->string('original_name');

            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);

            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();

            $table->string('extension', 20)->nullable();

            $table->nullableMorphs('mediable'); // product/vendor/user

            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->boolean('is_public')->default(true);

            $table->timestamps();

            $table->index('mime_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};

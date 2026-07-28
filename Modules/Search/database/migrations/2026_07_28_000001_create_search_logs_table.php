<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();
            $table->string('query', 200);
            $table->string('normalized_query', 200)->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('session_id', 100)->nullable()->index();
            $table->ipAddress('ip_address')->nullable();
            $table->json('filters')->nullable();
            $table->integer('results_count')->default(0);
            $table->float('search_duration_ms')->nullable();
            $table->string('source', 20)->default('web');
            $table->boolean('has_results')->default(false);
            $table->timestamps();

            $table->index(['created_at']);
            $table->index(['normalized_query', 'created_at'], 'search_logs_query_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_logs');
    }
};

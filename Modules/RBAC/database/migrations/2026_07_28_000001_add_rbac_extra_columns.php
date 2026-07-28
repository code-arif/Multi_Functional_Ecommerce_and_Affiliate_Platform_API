<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('roles')) {
            Schema::table('roles', function (Blueprint $table) {
                if (!Schema::hasColumn('roles', 'display_name')) {
                    $table->string('display_name', 150)->nullable()->after('name');
                }
                if (!Schema::hasColumn('roles', 'description')) {
                    $table->text('description')->nullable()->after('guard_name');
                }
            });
        }

        if (Schema::hasTable('permissions')) {
            Schema::table('permissions', function (Blueprint $table) {
                if (!Schema::hasColumn('permissions', 'display_name')) {
                    $table->string('display_name', 150)->nullable()->after('name');
                }
                if (!Schema::hasColumn('permissions', 'group')) {
                    $table->string('group', 50)->nullable()->after('guard_name')->index();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('roles')) {
            Schema::table('roles', function (Blueprint $table) {
                if (Schema::hasColumn('roles', 'display_name')) {
                    $table->dropColumn('display_name');
                }
                if (Schema::hasColumn('roles', 'description')) {
                    $table->dropColumn('description');
                }
            });
        }

        if (Schema::hasTable('permissions')) {
            Schema::table('permissions', function (Blueprint $table) {
                if (Schema::hasColumn('permissions', 'display_name')) {
                    $table->dropColumn('display_name');
                }
                if (Schema::hasColumn('permissions', 'group')) {
                    $table->dropColumn('group');
                }
            });
        }
    }
};

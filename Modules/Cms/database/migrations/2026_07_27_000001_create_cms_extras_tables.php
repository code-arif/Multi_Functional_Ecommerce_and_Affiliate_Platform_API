<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fix cms_pages table — add missing columns
        if (Schema::hasTable('cms_pages')) {
            Schema::table('cms_pages', function (Blueprint $table) {
                if (!Schema::hasColumn('cms_pages', 'title')) {
                    $table->string('title', 200)->after('id');
                }
                if (!Schema::hasColumn('cms_pages', 'slug')) {
                    $table->string('slug', 200)->unique()->nullable()->after('title');
                }
                if (!Schema::hasColumn('cms_pages', 'content')) {
                    $table->longText('content')->nullable()->after('slug');
                }
                if (!Schema::hasColumn('cms_pages', 'excerpt')) {
                    $table->text('excerpt')->nullable()->after('content');
                }
                if (!Schema::hasColumn('cms_pages', 'meta_title')) {
                    $table->string('meta_title', 100)->nullable()->after('excerpt');
                }
                if (!Schema::hasColumn('cms_pages', 'meta_description')) {
                    $table->string('meta_description', 255)->nullable()->after('meta_title');
                }
                if (!Schema::hasColumn('cms_pages', 'meta_keywords')) {
                    $table->string('meta_keywords', 255)->nullable()->after('meta_description');
                }
                if (!Schema::hasColumn('cms_pages', 'og_image')) {
                    $table->string('og_image')->nullable()->after('meta_keywords');
                }
                if (!Schema::hasColumn('cms_pages', 'template')) {
                    $table->string('template', 50)->default('default')->after('og_image');
                }
                if (!Schema::hasColumn('cms_pages', 'is_published')) {
                    $table->boolean('is_published')->default(false)->after('template');
                }
                if (!Schema::hasColumn('cms_pages', 'published_at')) {
                    $table->timestamp('published_at')->nullable()->after('is_published');
                }
                if (!Schema::hasColumn('cms_pages', 'order')) {
                    $table->integer('order')->default(0)->after('published_at');
                }
                if (!Schema::hasColumn('cms_pages', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }

        // Create cms_blocks table
        if (!Schema::hasTable('cms_blocks')) {
            Schema::create('cms_blocks', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('type', 50)->default('html'); // html, markdown, image, slider
                $table->longText('content')->nullable();
                $table->json('data')->nullable(); // Additional structured data per type
                $table->boolean('is_active')->default(true);
                $table->integer('order')->default(0);
                $table->timestamps();
                $table->softDeletes();

                $table->index('slug');
                $table->index('type');
            });
        }

        // Create cms_menus table
        if (!Schema::hasTable('cms_menus')) {
            Schema::create('cms_menus', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('location', 50); // header, footer, sidebar, mobile
                $table->json('items'); // Nested menu items structure
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index('location');
                $table->index('slug');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_menus');
        Schema::dropIfExists('cms_blocks');

        Schema::table('cms_pages', function (Blueprint $table) {
            $columns = [
                'title', 'slug', 'content', 'excerpt', 'meta_title',
                'meta_description', 'meta_keywords', 'og_image',
                'template', 'is_published', 'published_at', 'order',
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('cms_pages', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

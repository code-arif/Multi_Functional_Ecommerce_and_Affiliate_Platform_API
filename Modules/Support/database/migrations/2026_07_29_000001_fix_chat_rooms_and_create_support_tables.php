<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fix chat_rooms — add missing columns the model expects
        if (Schema::hasTable('chat_rooms')) {
            Schema::table('chat_rooms', function (Blueprint $table) {
                if (!Schema::hasColumn('chat_rooms', 'order_id')) {
                    $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete()->after('user_id');
                }
                if (!Schema::hasColumn('chat_rooms', 'subject')) {
                    $table->string('subject', 255)->nullable()->after('guest_email');
                }
                if (!Schema::hasColumn('chat_rooms', 'assigned_to')) {
                    $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete()->after('status');
                }
                if (!Schema::hasColumn('chat_rooms', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }

        // Fix chat_messages — add the type column the ChatMessage model expects
        if (Schema::hasTable('chat_messages')) {
            Schema::table('chat_messages', function (Blueprint $table) {
                if (!Schema::hasColumn('chat_messages', 'type')) {
                    $table->string('type', 20)->default('text')->after('message');
                }
            });
        }

        // Create tickets table
        if (!Schema::hasTable('tickets')) {
            Schema::create('tickets', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('ticket_number')->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
                $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
                $table->string('category', 100)->default('general');
                $table->string('subject', 255);
                $table->text('description');
                $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
                $table->enum('status', ['open', 'in_progress', 'waiting_on_customer', 'waiting_on_vendor', 'resolved', 'closed'])->default('open');
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('ticket_number');
                $table->index('user_id');
                $table->index('status');
                $table->index('priority');
            });
        }

        // Create ticket_messages table
        if (!Schema::hasTable('ticket_messages')) {
            Schema::create('ticket_messages', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->text('message');
                $table->json('attachments')->nullable();
                $table->boolean('is_staff_reply')->default(false);
                $table->timestamps();

                $table->index('ticket_id');
            });
        }

        // Create faq_categories table
        if (!Schema::hasTable('faq_categories')) {
            Schema::create('faq_categories', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('name', 100);
                $table->string('slug', 120)->unique();
                $table->text('description')->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('slug');
                $table->index('sort_order');
            });
        }

        // Create faqs table
        if (!Schema::hasTable('faqs')) {
            Schema::create('faqs', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('category_id')->nullable()->constrained('faq_categories')->nullOnDelete();
                $table->string('question', 500);
                $table->text('answer');
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index('category_id');
                $table->index('sort_order');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('faq_categories');
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('tickets');

        Schema::table('chat_rooms', function (Blueprint $table) {
            $table->dropColumn(['order_id', 'subject', 'assigned_to', 'deleted_at']);
        });
    }
};

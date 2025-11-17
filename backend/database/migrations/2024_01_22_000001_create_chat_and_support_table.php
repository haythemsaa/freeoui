<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Chat conversations
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('conversation_type'); // 'user_support', 'user_merchant', 'merchant_support'
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('merchant_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('admin_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('subject')->nullable();
            $table->string('status'); // 'open', 'in_progress', 'resolved', 'closed'
            $table->string('priority')->default('normal'); // 'low', 'normal', 'high', 'urgent'
            $table->timestamp('last_message_at')->nullable();
            $table->foreignId('last_message_by')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('unread_count_user')->default(0);
            $table->integer('unread_count_merchant')->default(0);
            $table->integer('unread_count_admin')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['merchant_id', 'status']);
            $table->index('status');
            $table->index('last_message_at');
        });

        // Chat messages
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->string('sender_type'); // 'user', 'merchant', 'admin', 'bot'
            $table->text('message');
            $table->string('message_type')->default('text'); // 'text', 'image', 'file', 'system', 'automated'
            $table->json('attachments')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->boolean('is_edited')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('conversation_id');
            $table->index(['conversation_id', 'created_at']);
            $table->index('is_read');
        });

        // Support tickets (for structured support)
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('merchant_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('conversation_id')->nullable()->constrained()->onDelete('set null');
            $table->string('category'); // 'account', 'payment', 'technical', 'merchant', 'advantage', 'other'
            $table->string('subject');
            $table->text('description');
            $table->string('status')->default('open'); // 'open', 'in_progress', 'waiting_customer', 'resolved', 'closed'
            $table->string('priority')->default('normal');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->integer('satisfaction_rating')->nullable(); // 1-5
            $table->text('satisfaction_comment')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('ticket_number');
            $table->index(['user_id', 'status']);
            $table->index(['merchant_id', 'status']);
            $table->index('status');
            $table->index('category');
        });

        // Quick replies / canned responses
        Schema::create('quick_replies', function (Blueprint $table) {
            $table->id();
            $table->string('shortcut')->unique();
            $table->string('title');
            $table->text('message');
            $table->string('category')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('usage_count')->default(0);
            $table->timestamps();

            $table->index('shortcut');
            $table->index('is_active');
        });

        // Chat bots / automated responses
        Schema::create('chatbot_intents', function (Blueprint $table) {
            $table->id();
            $table->string('intent_name')->unique();
            $table->json('training_phrases'); // Array of phrases to match
            $table->text('response');
            $table->json('response_variations')->nullable(); // Multiple response options
            $table->string('action')->nullable(); // 'create_ticket', 'transfer_agent', 'provide_info'
            $table->json('required_params')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('confidence_threshold')->default(70); // 0-100
            $table->integer('matched_count')->default(0);
            $table->timestamps();

            $table->index('intent_name');
            $table->index('is_active');
        });

        // Add support fields to users
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_support_agent')->default(false)->after('is_verified');
            $table->integer('support_tickets_handled')->default(0)->after('is_support_agent');
            $table->decimal('support_satisfaction_avg', 3, 2)->default(0)->after('support_tickets_handled');
        });

        // Add support fields to merchants
        Schema::table('merchants', function (Blueprint $table) {
            $table->boolean('chat_enabled')->default(true)->after('total_ad_spend');
            $table->json('chat_availability')->nullable()->after('chat_enabled'); // Business hours
            $table->text('chat_welcome_message')->nullable()->after('chat_availability');
            $table->boolean('auto_reply_enabled')->default(false)->after('chat_welcome_message');
            $table->text('auto_reply_message')->nullable()->after('auto_reply_enabled');
            $table->decimal('chat_response_time_avg', 5, 1)->default(0)->after('auto_reply_message'); // minutes
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn([
                'chat_enabled',
                'chat_availability',
                'chat_welcome_message',
                'auto_reply_enabled',
                'auto_reply_message',
                'chat_response_time_avg',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_support_agent',
                'support_tickets_handled',
                'support_satisfaction_avg',
            ]);
        });

        Schema::dropIfExists('chatbot_intents');
        Schema::dropIfExists('quick_replies');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
    }
};

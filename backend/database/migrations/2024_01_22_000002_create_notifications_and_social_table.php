<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Advanced notifications
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('notification_type'); // 'push', 'email', 'sms', 'in_app'
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('message');
            $table->string('category'); // 'advantage', 'booking', 'payment', 'loyalty', 'achievement', 'chat', 'system'
            $table->string('priority')->default('normal'); // 'low', 'normal', 'high', 'urgent'
            $table->json('data')->nullable(); // Deep link data, action buttons
            $table->string('action_url')->nullable();
            $table->string('image_url')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_sent')->default(false);
            $table->boolean('is_read')->default(false);
            $table->boolean('is_clicked')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('delivery_status')->nullable(); // FCM/email/SMS delivery status
            $table->timestamps();

            $table->index('user_id');
            $table->index(['user_id', 'is_read']);
            $table->index(['user_id', 'category']);
            $table->index('created_at');
        });

        // Notification preferences
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');

            // Channel preferences
            $table->boolean('push_enabled')->default(true);
            $table->boolean('email_enabled')->default(true);
            $table->boolean('sms_enabled')->default(false);

            // Category preferences
            $table->boolean('advantages_nearby')->default(true);
            $table->boolean('advantages_favorites')->default(true);
            $table->boolean('bookings')->default(true);
            $table->boolean('payments')->default(true);
            $table->boolean('loyalty_rewards')->default(true);
            $table->boolean('achievements')->default(true);
            $table->boolean('chat_messages')->default(true);
            $table->boolean('marketing')->default(true);
            $table->boolean('system_updates')->default(true);

            // Advanced preferences
            $table->json('quiet_hours')->nullable(); // {'start': '22:00', 'end': '08:00'}
            $table->json('frequency_limits')->nullable(); // Max notifications per day/hour
            $table->integer('proximity_radius_meters')->default(5000);
            $table->timestamps();

            $table->index('user_id');
        });

        // Social sharing & invitations
        Schema::create('social_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->morphs('shareable'); // Advantage, Merchant, Achievement
            $table->string('platform'); // 'facebook', 'instagram', 'whatsapp', 'twitter', 'messenger', 'native'
            $table->string('share_type'); // 'advantage', 'merchant', 'achievement', 'referral', 'qr_code'
            $table->text('share_url')->nullable();
            $table->text('share_text')->nullable();
            $table->string('share_image_url')->nullable();
            $table->integer('click_count')->default(0);
            $table->integer('conversion_count')->default(0);
            $table->timestamps();

            $table->index('user_id');
            $table->index(['shareable_type', 'shareable_id']);
            $table->index('platform');
            $table->index('created_at');
        });

        // Social invitations
        Schema::create('social_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inviter_id')->constrained('users')->onDelete('cascade');
            $table->string('invitation_code')->unique();
            $table->string('contact_method'); // 'email', 'phone', 'whatsapp', 'messenger', 'link'
            $table->string('contact_value')->nullable(); // Email/phone if applicable
            $table->string('platform'); // 'whatsapp', 'messenger', 'email', 'sms', 'link'
            $table->string('status')->default('sent'); // 'sent', 'viewed', 'registered', 'expired'
            $table->foreignId('invitee_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('rewarded_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('inviter_id');
            $table->index('invitation_code');
            $table->index('status');
        });

        // User collections/lists (save for later)
        Schema::create('user_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->boolean('is_public')->default(false);
            $table->integer('items_count')->default(0);
            $table->integer('views_count')->default(0);
            $table->integer('shares_count')->default(0);
            $table->timestamps();

            $table->index('user_id');
            $table->index('is_public');
        });

        Schema::create('collection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained('user_collections')->onDelete('cascade');
            $table->morphs('collectable'); // Advantage, Merchant
            $table->text('note')->nullable();
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->index('collection_id');
            $table->index(['collectable_type', 'collectable_id']);
        });

        // Update favorites table to be more social
        Schema::table('user_favorites', function (Blueprint $table) {
            $table->text('note')->nullable()->after('advantage_id');
            $table->boolean('notify_updates')->default(true)->after('note');
            $table->boolean('is_public')->default(false)->after('notify_updates');
            $table->json('tags')->nullable()->after('is_public');
        });
    }

    public function down(): void
    {
        Schema::table('user_favorites', function (Blueprint $table) {
            $table->dropColumn(['note', 'notify_updates', 'is_public', 'tags']);
        });

        Schema::dropIfExists('collection_items');
        Schema::dropIfExists('user_collections');
        Schema::dropIfExists('social_invitations');
        Schema::dropIfExists('social_shares');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notifications');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Stories & Posts
        Schema::create('stories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('merchant_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('type', ['image', 'video'])->default('image');
            $table->string('media_url');
            $table->string('thumbnail_url')->nullable();
            $table->text('caption')->nullable();
            $table->integer('duration')->default(5);
            $table->string('background_color')->default('#000000');
            $table->integer('views_count')->default(0);
            $table->timestamp('expires_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'is_active', 'expires_at']);
        });

        Schema::create('story_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('story_id')->constrained()->onDelete('cascade');
            $table->timestamp('viewed_at');
            $table->integer('watch_duration')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'story_id']);
        });

        // Referral System
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_tier')->nullable()->after('successful_referrals');
        });

        Schema::table('referrals', function (Blueprint $table) {
            $table->integer('tier')->default(1)->after('referee_id');
            $table->integer('referrer_reward')->default(0)->after('tier');
            $table->integer('referee_reward')->default(0)->after('referrer_reward');
        });

        // Cashback System
        Schema::create('cashbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('transaction_id')->constrained()->onDelete('cascade');
            $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->decimal('rate', 5, 2);
            $table->enum('status', ['pending', 'processed', 'cancelled'])->default('pending');
            $table->timestamp('eligible_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'eligible_at']);
        });

        // Gift Cards
        Schema::create('gift_cards', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('redeemed_by_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('recipient_email')->nullable();
            $table->string('recipient_phone')->nullable();
            $table->decimal('amount', 10, 2);
            $table->decimal('balance', 10, 2);
            $table->text('message')->nullable();
            $table->string('design_template')->default('default');
            $table->enum('status', ['active', 'redeemed', 'expired', 'cancelled'])->default('active');
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['code', 'status']);
        });

        // User Friends (for leaderboards)
        Schema::create('user_friends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('friend_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['pending', 'accepted', 'blocked'])->default('accepted');
            $table->timestamps();

            $table->unique(['user_id', 'friend_id']);
        });

        // Merchant Followers
        Schema::create('merchant_followers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'merchant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_followers');
        Schema::dropIfExists('user_friends');
        Schema::dropIfExists('gift_cards');
        Schema::dropIfExists('cashbacks');
        Schema::table('referrals', function (Blueprint $table) {
            $table->dropColumn(['tier', 'referrer_reward', 'referee_reward']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('referral_tier');
        });
        Schema::dropIfExists('story_views');
        Schema::dropIfExists('stories');
    }
};

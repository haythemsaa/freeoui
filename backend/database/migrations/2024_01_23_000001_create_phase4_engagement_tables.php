<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Subscriptions (FreeOui Plus)
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('plan_type'); // 'monthly', 'yearly'
            $table->decimal('price', 10, 3);
            $table->integer('discount_percentage')->default(25);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamp('renews_at')->nullable();
            $table->string('status'); // 'active', 'cancelled', 'expired', 'pending'
            $table->foreignId('payment_id')->nullable()->constrained();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index('ends_at');
        });

        // Mayorships
        Schema::create('mayorships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
            $table->integer('checkin_count')->default(1);
            $table->timestamp('claimed_at');
            $table->timestamp('last_checkin_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['merchant_id', 'is_active']);
            $table->index(['user_id', 'is_active']);
            $table->index('last_checkin_at');
        });

        // Sticker Collections
        Schema::create('sticker_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->string('sticker_type'); // 'category_bronze', 'category_silver', etc.
            $table->string('sticker_tier'); // 'bronze', 'silver', 'gold', 'diamond'
            $table->integer('visit_count')->default(1);
            $table->decimal('coin_multiplier', 3, 2)->default(1.0);
            $table->timestamp('unlocked_at');
            $table->timestamps();
            
            $table->unique(['user_id', 'category_id', 'sticker_tier']);
            $table->index(['user_id', 'sticker_tier']);
        });

        // Challenges
        Schema::create('challenges', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->string('challenge_type'); // 'visit_count', 'spend_amount', 'category_explore', 'governorate_explore'
            $table->json('criteria'); // {"visit_count": 5, "category_id": 1}
            $table->integer('reward_points');
            $table->string('badge_icon')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_recurring')->default(false); // weekly, monthly
            $table->string('recurrence_type')->nullable(); // 'weekly', 'monthly'
            $table->timestamps();
            
            $table->index(['is_active', 'starts_at', 'ends_at']);
        });

        // Challenge Participations
        Schema::create('challenge_participations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('challenge_id')->constrained()->onDelete('cascade');
            $table->json('progress'); // {"current": 3, "target": 5}
            $table->integer('progress_percentage')->default(0);
            $table->string('status'); // 'in_progress', 'completed', 'failed', 'expired'
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'challenge_id']);
            $table->index(['user_id', 'status']);
            $table->index('progress_percentage');
        });

        // Leaderboard Periods (cache pour performance)
        Schema::create('leaderboard_periods', function (Blueprint $table) {
            $table->id();
            $table->string('period_type'); // 'weekly', 'monthly', 'all_time'
            $table->date('period_start');
            $table->date('period_end')->nullable();
            $table->json('rankings'); // cache des classements
            $table->timestamp('last_updated_at');
            $table->timestamps();
            
            $table->index(['period_type', 'period_start']);
        });

        // Add subscription fields to users
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_premium')->default(false)->after('points_balance');
            $table->timestamp('premium_until')->nullable()->after('is_premium');
            $table->integer('coins_balance')->default(0)->after('premium_until');
            $table->integer('checkin_streak')->default(0)->after('coins_balance');
            $table->date('last_checkin_date')->nullable()->after('checkin_streak');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('challenge_participations');
        Schema::dropIfExists('challenges');
        Schema::dropIfExists('sticker_collections');
        Schema::dropIfExists('mayorships');
        Schema::dropIfExists('leaderboard_periods');
        Schema::dropIfExists('subscriptions');
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_premium',
                'premium_until',
                'coins_balance',
                'checkin_streak',
                'last_checkin_date',
            ]);
        });
    }
};

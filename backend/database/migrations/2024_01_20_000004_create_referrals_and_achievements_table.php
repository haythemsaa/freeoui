<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->onDelete('cascade'); // Parrain
            $table->foreignId('referee_id')->nullable()->constrained('users')->onDelete('cascade'); // Filleul
            $table->string('referral_code')->unique();
            $table->string('status'); // 'pending', 'completed', 'rewarded'
            $table->integer('referrer_reward_points')->default(0);
            $table->integer('referee_reward_points')->default(0);
            $table->decimal('referrer_reward_amount', 10, 3)->default(0); // TND
            $table->decimal('referee_reward_amount', 10, 3)->default(0); // TND
            $table->timestamp('completed_at')->nullable(); // Quand le filleul a rempli les conditions
            $table->timestamp('rewarded_at')->nullable(); // Quand les récompenses ont été données
            $table->timestamps();

            $table->index('referral_code');
            $table->index(['referrer_id', 'status']);
        });

        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // 'first_scan', 'scan_10', 'scan_50', etc.
            $table->string('name');
            $table->text('description');
            $table->string('icon'); // URL ou emoji
            $table->string('tier'); // 'bronze', 'silver', 'gold', 'platinum'
            $table->integer('points_reward')->default(0); // Points gagnés
            $table->json('criteria'); // Conditions pour débloquer
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();

            $table->index('is_active');
        });

        Schema::create('user_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('achievement_id')->constrained()->onDelete('cascade');
            $table->integer('progress')->default(0); // Progression actuelle
            $table->integer('target')->default(1); // Objectif
            $table->boolean('is_unlocked')->default(false);
            $table->timestamp('unlocked_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'achievement_id']);
            $table->index(['user_id', 'is_unlocked']);
        });

        // Ajouter référent sur users
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code')->unique()->nullable()->after('level');
            $table->foreignId('referred_by_id')->nullable()->constrained('users')->onDelete('set null')->after('referral_code');
            $table->integer('successful_referrals')->default(0)->after('referred_by_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by_id']);
            $table->dropColumn(['referral_code', 'referred_by_id', 'successful_referrals']);
        });

        Schema::dropIfExists('user_achievements');
        Schema::dropIfExists('achievements');
        Schema::dropIfExists('referrals');
    }
};

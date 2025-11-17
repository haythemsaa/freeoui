<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('points')->default(0);
            $table->string('type'); // 'earned', 'redeemed', 'expired', 'bonus'
            $table->text('description');
            $table->morphs('pointable'); // QRCode, Referral, etc.
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index('expires_at');
        });

        Schema::create('loyalty_rewards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->integer('points_required');
            $table->string('type'); // 'discount', 'free_item', 'advantage', 'cashback'
            $table->json('config'); // Configuration spécifique au type
            $table->boolean('is_active')->default(true);
            $table->integer('stock')->nullable(); // null = unlimited
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->timestamps();

            $table->index('is_active');
            $table->index('points_required');
        });

        Schema::create('loyalty_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('loyalty_reward_id')->constrained()->onDelete('cascade');
            $table->integer('points_spent');
            $table->string('status'); // 'pending', 'confirmed', 'used', 'expired', 'cancelled'
            $table->string('code')->unique(); // Code unique pour utilisation
            $table->timestamp('used_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('code');
        });

        // Ajouter colonne balance sur users
        Schema::table('users', function (Blueprint $table) {
            $table->integer('loyalty_points_balance')->default(0)->after('level');
            $table->integer('loyalty_points_lifetime')->default(0)->after('loyalty_points_balance');
            $table->string('loyalty_tier')->default('bronze')->after('loyalty_points_lifetime'); // bronze, silver, gold, platinum, diamond
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['loyalty_points_balance', 'loyalty_points_lifetime', 'loyalty_tier']);
        });

        Schema::dropIfExists('loyalty_redemptions');
        Schema::dropIfExists('loyalty_rewards');
        Schema::dropIfExists('loyalty_points');
    }
};

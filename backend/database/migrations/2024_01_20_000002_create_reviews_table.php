<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->morphs('reviewable'); // Merchant, Advantage
            $table->integer('rating'); // 1-5 stars
            $table->text('comment')->nullable();
            $table->json('photos')->nullable(); // Array of photo URLs
            $table->boolean('is_verified')->default(false); // Has actually used the service
            $table->boolean('is_approved')->default(true); // Moderation
            $table->integer('helpful_count')->default(0);
            $table->integer('not_helpful_count')->default(0);
            $table->timestamp('merchant_responded_at')->nullable();
            $table->text('merchant_response')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['reviewable_type', 'reviewable_id']);
            $table->index(['user_id', 'reviewable_type', 'reviewable_id'], 'user_reviewable_index');
            $table->index('rating');
            $table->index('is_approved');
        });

        Schema::create('review_helpfulness', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('is_helpful'); // true = helpful, false = not helpful
            $table->timestamps();

            $table->unique(['review_id', 'user_id']);
        });

        // Ajouter colonnes rating sur merchants
        Schema::table('merchants', function (Blueprint $table) {
            $table->decimal('rating_average', 3, 2)->default(0)->after('is_active');
            $table->integer('rating_count')->default(0)->after('rating_average');
        });

        // Ajouter colonnes rating sur advantages
        Schema::table('advantages', function (Blueprint $table) {
            $table->decimal('rating_average', 3, 2)->default(0)->after('usage_count');
            $table->integer('rating_count')->default(0)->after('rating_average');
        });
    }

    public function down(): void
    {
        Schema::table('advantages', function (Blueprint $table) {
            $table->dropColumn(['rating_average', 'rating_count']);
        });

        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn(['rating_average', 'rating_count']);
        });

        Schema::dropIfExists('review_helpfulness');
        Schema::dropIfExists('reviews');
    }
};

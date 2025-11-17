<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Advertisement boosts
        Schema::create('boosts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
            $table->morphs('boostable'); // Merchant or Advantage
            $table->string('boost_type'); // 'featured', 'top_position', 'homepage', 'category_top', 'notification'
            $table->decimal('daily_budget', 10, 3);
            $table->decimal('total_budget', 10, 3);
            $table->decimal('spent_amount', 10, 3)->default(0);
            $table->decimal('cost_per_view', 10, 3)->default(0.100); // 100 millimes per view
            $table->decimal('cost_per_click', 10, 3)->default(0.500); // 500 millimes per click
            $table->integer('impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->integer('conversions')->default(0); // QR codes scanned
            $table->decimal('conversion_rate', 5, 2)->default(0);
            $table->json('target_audience')->nullable(); // Age, location, interests
            $table->json('target_locations')->nullable(); // Specific governorates/cities
            $table->string('status'); // 'pending', 'active', 'paused', 'completed', 'cancelled'
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('priority')->default(0); // Higher priority = shown first
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('merchant_id');
            $table->index(['boostable_type', 'boostable_id']);
            $table->index('status');
            $table->index(['start_date', 'end_date']);
            $table->index('priority');
        });

        // Boost performance tracking
        Schema::create('boost_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boost_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('event_type'); // 'impression', 'click', 'conversion', 'charge'
            $table->decimal('charge_amount', 10, 3)->default(0);
            $table->string('user_agent')->nullable();
            $table->string('ip_address')->nullable();
            $table->point('location')->nullable(); // PostGIS
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');

            $table->index('boost_id');
            $table->index('user_id');
            $table->index('event_type');
            $table->index('created_at');
        });

        // Promotional campaigns
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('campaign_type'); // 'discount', 'cashback', 'bonus_points', 'free_delivery'
            $table->json('rules'); // Campaign rules and conditions
            $table->decimal('budget', 10, 3)->nullable();
            $table->decimal('spent', 10, 3)->default(0);
            $table->integer('max_participants')->nullable();
            $table->integer('current_participants')->default(0);
            $table->string('status'); // 'draft', 'scheduled', 'active', 'paused', 'completed', 'cancelled'
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('views_count')->default(0);
            $table->integer('participations_count')->default(0);
            $table->integer('conversions_count')->default(0);
            $table->timestamps();

            $table->index('merchant_id');
            $table->index('status');
            $table->index(['start_date', 'end_date']);
        });

        // Add boost/campaign fields to advantages
        Schema::table('advantages', function (Blueprint $table) {
            $table->boolean('is_boosted')->default(false)->after('is_exclusive');
            $table->integer('boost_priority')->default(0)->after('is_boosted');
            $table->timestamp('boosted_until')->nullable()->after('boost_priority');
        });

        // Add boost fields to merchants
        Schema::table('merchants', function (Blueprint $table) {
            $table->boolean('is_boosted')->default(false)->after('featured');
            $table->integer('boost_priority')->default(0)->after('is_boosted');
            $table->timestamp('boosted_until')->nullable()->after('boost_priority');
            $table->decimal('total_ad_spend', 10, 3)->default(0)->after('boosted_until');
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn(['is_boosted', 'boost_priority', 'boosted_until', 'total_ad_spend']);
        });

        Schema::table('advantages', function (Blueprint $table) {
            $table->dropColumn(['is_boosted', 'boost_priority', 'boosted_until']);
        });

        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('boost_events');
        Schema::dropIfExists('boosts');
    }
};

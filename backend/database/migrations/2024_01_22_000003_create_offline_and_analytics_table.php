<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Offline data sync
        Schema::create('sync_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('action_type'); // 'create', 'update', 'delete'
            $table->string('entity_type'); // 'favorite', 'review', 'booking', 'qr_scan'
            $table->string('entity_id')->nullable();
            $table->json('payload');
            $table->string('status')->default('pending'); // 'pending', 'processing', 'completed', 'failed'
            $table->integer('retry_count')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->string('client_uuid')->nullable(); // Client-side unique ID
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            $table->index(['user_id', 'status']);
            $table->index('client_uuid');
        });

        // Offline cache metadata
        Schema::create('offline_cache', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('cache_key');
            $table->string('entity_type'); // 'advantage', 'merchant', 'category'
            $table->json('data');
            $table->timestamp('cached_at');
            $table->timestamp('expires_at');
            $table->integer('size_bytes')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'cache_key']);
            $table->index('expires_at');
        });

        // User analytics events
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('event_name'); // 'screen_view', 'button_click', 'search', 'scroll', etc.
            $table->string('event_category'); // 'engagement', 'navigation', 'commerce', 'social'
            $table->json('properties')->nullable();
            $table->string('screen_name')->nullable();
            $table->string('platform'); // 'ios', 'android', 'web'
            $table->string('app_version')->nullable();
            $table->string('device_model')->nullable();
            $table->string('os_version')->nullable();
            $table->point('location')->nullable();
            $table->string('session_id')->nullable();
            $table->timestamp('created_at');

            $table->index('user_id');
            $table->index('event_name');
            $table->index('event_category');
            $table->index('session_id');
            $table->index('created_at');
        });

        // User sessions
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration_seconds')->default(0);
            $table->string('platform'); // 'ios', 'android', 'web'
            $table->string('app_version')->nullable();
            $table->string('device_model')->nullable();
            $table->string('os_version')->nullable();
            $table->integer('screens_viewed')->default(0);
            $table->integer('actions_performed')->default(0);
            $table->point('start_location')->nullable();
            $table->point('end_location')->nullable();
            $table->json('metadata')->nullable();

            $table->index('session_id');
            $table->index('user_id');
            $table->index('started_at');
        });

        // User cohorts for analytics
        Schema::create('user_cohorts', function (Blueprint $table) {
            $table->id();
            $table->string('cohort_name');
            $table->text('description')->nullable();
            $table->string('cohort_type'); // 'registration_date', 'first_purchase', 'behavior', 'custom'
            $table->date('cohort_date')->nullable();
            $table->json('criteria')->nullable();
            $table->integer('users_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('cohort_name');
            $table->index('cohort_date');
        });

        Schema::create('cohort_users', function (Blueprint $table) {
            $table->foreignId('cohort_id')->constrained('user_cohorts')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('joined_at');

            $table->primary(['cohort_id', 'user_id']);
            $table->index('cohort_id');
            $table->index('user_id');
        });

        // A/B testing experiments
        Schema::create('experiments', function (Blueprint $table) {
            $table->id();
            $table->string('experiment_key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('draft'); // 'draft', 'running', 'paused', 'completed'
            $table->json('variants'); // [{'name': 'control', 'weight': 50}, {'name': 'variant_a', 'weight': 50}]
            $table->string('metric'); // 'conversion_rate', 'retention', 'revenue'
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('sample_size_target')->nullable();
            $table->integer('current_sample_size')->default(0);
            $table->json('results')->nullable();
            $table->timestamps();

            $table->index('experiment_key');
            $table->index('status');
        });

        Schema::create('experiment_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experiment_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('variant_name');
            $table->boolean('has_converted')->default(false);
            $table->timestamp('converted_at')->nullable();
            $table->json('conversion_data')->nullable();
            $table->timestamp('assigned_at');

            $table->index(['experiment_id', 'user_id']);
            $table->index(['experiment_id', 'variant_name']);
        });

        // Performance metrics
        Schema::create('performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('metric_name'); // 'api_response_time', 'page_load_time', 'query_time'
            $table->string('metric_type'); // 'latency', 'throughput', 'error_rate'
            $table->decimal('value', 10, 2);
            $table->string('unit'); // 'ms', 'seconds', 'count', 'percentage'
            $table->string('endpoint')->nullable();
            $table->string('platform')->nullable();
            $table->json('tags')->nullable();
            $table->timestamp('measured_at');

            $table->index('metric_name');
            $table->index('measured_at');
        });

        // Add analytics fields to users
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_active_at')->nullable()->after('last_login_at');
            $table->integer('total_sessions')->default(0)->after('last_active_at');
            $table->integer('total_session_duration_minutes')->default(0)->after('total_sessions');
            $table->decimal('avg_session_duration_minutes', 8, 2)->default(0)->after('total_session_duration_minutes');
            $table->integer('screens_viewed_total')->default(0)->after('avg_session_duration_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'last_active_at',
                'total_sessions',
                'total_session_duration_minutes',
                'avg_session_duration_minutes',
                'screens_viewed_total',
            ]);
        });

        Schema::dropIfExists('performance_metrics');
        Schema::dropIfExists('experiment_assignments');
        Schema::dropIfExists('experiments');
        Schema::dropIfExists('cohort_users');
        Schema::dropIfExists('user_cohorts');
        Schema::dropIfExists('user_sessions');
        Schema::dropIfExists('analytics_events');
        Schema::dropIfExists('offline_cache');
        Schema::dropIfExists('sync_queue');
    }
};

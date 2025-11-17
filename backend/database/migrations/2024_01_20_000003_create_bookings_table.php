<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_number')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
            $table->foreignId('advantage_id')->nullable()->constrained()->onDelete('set null');
            $table->dateTime('booking_date');
            $table->time('booking_time');
            $table->integer('party_size')->default(1); // Nombre de personnes
            $table->string('status'); // 'pending', 'confirmed', 'cancelled', 'completed', 'no_show'
            $table->text('special_requests')->nullable();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['merchant_id', 'booking_date']);
            $table->index(['user_id', 'status']);
            $table->index('booking_date');
            $table->index('status');
        });

        Schema::create('booking_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
            $table->string('day_of_week'); // 'monday', 'tuesday', etc.
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('max_capacity'); // Capacité totale
            $table->integer('duration_minutes')->default(60); // Durée d'un slot
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['merchant_id', 'day_of_week', 'is_active']);
        });

        Schema::create('booking_blackouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->time('start_time')->nullable(); // null = toute la journée
            $table->time('end_time')->nullable();
            $table->text('reason');
            $table->timestamps();

            $table->index(['merchant_id', 'date']);
        });

        // Ajouter config réservation sur merchants
        Schema::table('merchants', function (Blueprint $table) {
            $table->boolean('accepts_bookings')->default(false)->after('is_active');
            $table->integer('booking_advance_hours')->default(2)->after('accepts_bookings'); // Délai minimum
            $table->integer('booking_max_days')->default(30)->after('booking_advance_hours'); // Combien de jours à l'avance
            $table->boolean('requires_confirmation')->default(true)->after('booking_max_days');
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn([
                'accepts_bookings',
                'booking_advance_hours',
                'booking_max_days',
                'requires_confirmation'
            ]);
        });

        Schema::dropIfExists('booking_blackouts');
        Schema::dropIfExists('booking_slots');
        Schema::dropIfExists('bookings');
    }
};

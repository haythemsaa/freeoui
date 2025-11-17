<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Merchant extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'business_name',
        'legal_name',
        'trade_registry',
        'tax_id',
        'email',
        'phone_number',
        'mobile_number',
        'website',
        'address_line1',
        'address_line2',
        'governorate_id',
        'city_id',
        'district',
        'postal_code',
        'latitude',
        'longitude',
        'influence_radius_meters',
        'category_id',
        'subcategory_id',
        'logo_url',
        'cover_image_url',
        'photos',
        'description',
        'opening_hours',
        'plan_id',
        'subscription_status',
        'subscription_start_date',
        'subscription_end_date',
        'monthly_alerts_quota',
        'monthly_alerts_sent',
        'average_rating',
        'reviews_count',
        'total_advantages',
        'total_transactions',
        'status',
        'verified',
        'featured',
        'owner_user_id',
        'patente_url',
        'cin_url',
        'rib_url',
        // Reviews
        'rating_average',
        'rating_count',
        // Booking fields
        'accepts_bookings',
        'booking_advance_hours',
        'booking_max_days',
        'requires_confirmation',
        'auto_confirm_bookings',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'influence_radius_meters' => 'integer',
        'photos' => 'array',
        'opening_hours' => 'json',
        'subscription_start_date' => 'date',
        'subscription_end_date' => 'date',
        'monthly_alerts_quota' => 'integer',
        'monthly_alerts_sent' => 'integer',
        'average_rating' => 'decimal:2',
        'reviews_count' => 'integer',
        'total_advantages' => 'integer',
        'total_transactions' => 'integer',
        'verified' => 'boolean',
        'featured' => 'boolean',
        'rating_average' => 'decimal:2',
        'rating_count' => 'integer',
        'accepts_bookings' => 'boolean',
        'booking_advance_hours' => 'integer',
        'booking_max_days' => 'integer',
        'requires_confirmation' => 'boolean',
        'auto_confirm_bookings' => 'boolean',
    ];

    /**
     * Get the merchant's advantages
     */
    public function advantages(): HasMany
    {
        return $this->hasMany(Advantage::class);
    }

    /**
     * Get the merchant's active advantages
     */
    public function activeAdvantages(): HasMany
    {
        return $this->advantages()
            ->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    /**
     * Get the merchant's transactions
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the merchant's reviews (polymorphic)
     */
    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /**
     * Get the merchant's bookings
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Get the merchant's booking slots
     */
    public function bookingSlots(): HasMany
    {
        return $this->hasMany(BookingSlot::class);
    }

    /**
     * Get the merchant's booking blackouts
     */
    public function bookingBlackouts(): HasMany
    {
        return $this->hasMany(BookingBlackout::class);
    }

    /**
     * Get the merchant's payments
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the merchant's commissions
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    /**
     * Get the merchant's payouts
     */
    public function payouts(): HasMany
    {
        return $this->hasMany(MerchantPayout::class);
    }

    /**
     * Get the merchant's boosts
     */
    public function boosts(): HasMany
    {
        return $this->hasMany(Boost::class);
    }

    /**
     * Get the merchant's campaigns
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    /**
     * Get the merchant's proximity alerts
     */
    public function proximityAlerts(): HasMany
    {
        return $this->hasMany(ProximityAlertLog::class);
    }

    /**
     * Get the merchant's subscriptions
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(MerchantSubscription::class);
    }

    /**
     * Get the merchant's active subscription
     */
    public function activeSubscription()
    {
        return $this->hasOne(MerchantSubscription::class)
            ->where('status', 'active')
            ->where('end_date', '>=', now())
            ->latest();
    }

    /**
     * Get the merchant's category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the merchant's subcategory
     */
    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }

    /**
     * Get the merchant's governorate
     */
    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    /**
     * Get the merchant's city
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Get the merchant's owner
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * Check if merchant can send more alerts this month
     */
    public function canSendAlert(): bool
    {
        return $this->monthly_alerts_sent < $this->monthly_alerts_quota;
    }

    /**
     * Increment alerts sent counter
     */
    public function incrementAlertsSent(): void
    {
        $this->increment('monthly_alerts_sent');
    }

    /**
     * Reset monthly alerts counter
     */
    public function resetMonthlyAlerts(): void
    {
        $this->update(['monthly_alerts_sent' => 0]);
    }

    /**
     * Check if merchant is open now
     */
    public function isOpenNow(): bool
    {
        if (!$this->opening_hours) {
            return true; // Assume open if no hours set
        }

        $dayOfWeek = strtolower(now()->englishDayOfWeek);
        $currentTime = now()->format('H:i');

        $todayHours = $this->opening_hours[$dayOfWeek] ?? null;

        if (!$todayHours || empty($todayHours)) {
            return false; // Closed today
        }

        foreach ($todayHours as $slot) {
            if ($currentTime >= $slot['start'] && $currentTime <= $slot['end']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get distance from a location in meters
     */
    public function getDistanceFrom(float $latitude, float $longitude): float
    {
        return $this->calculateDistance(
            $this->latitude,
            $this->longitude,
            $latitude,
            $longitude
        );
    }

    /**
     * Calculate distance between two points using Haversine formula
     */
    protected function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Scope to get active merchants
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get verified merchants
     */
    public function scopeVerified($query)
    {
        return $query->where('verified', true);
    }

    /**
     * Scope to get featured merchants
     */
    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    /**
     * Scope to get merchants within radius of a location
     */
    public function scopeWithinRadius($query, float $latitude, float $longitude, int $radiusMeters)
    {
        return $query->selectRaw(
            "*,
            ST_Distance(
                ST_MakePoint(?, ?)::geography,
                ST_MakePoint(longitude, latitude)::geography
            ) as distance_meters",
            [$longitude, $latitude]
        )
            ->whereRaw(
                "ST_DWithin(
                ST_MakePoint(?, ?)::geography,
                ST_MakePoint(longitude, latitude)::geography,
                ?
            )",
                [$longitude, $latitude, $radiusMeters]
            )
            ->orderBy('distance_meters');
    }
}

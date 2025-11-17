<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'phone_number',
        'country_code',
        'email',
        'email_verified_at',
        'password',
        'google_id',
        'facebook_id',
        'first_name',
        'last_name',
        'avatar_url',
        'date_of_birth',
        'gender',
        'governorate_id',
        'city_id',
        'district',
        'postal_code',
        'address_details',
        'preferred_language',
        'fcm_token',
        'push_notifications',
        'email_notifications',
        'sms_notifications',
        'points_balance',
        'level',
        'total_savings_tnd',
        'is_active',
        'is_verified',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'date_of_birth' => 'date',
        'push_notifications' => 'boolean',
        'email_notifications' => 'boolean',
        'sms_notifications' => 'boolean',
        'points_balance' => 'integer',
        'level' => 'integer',
        'total_savings_tnd' => 'decimal:3',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    /**
     * Get the user's proximity preferences
     */
    public function proximityPreferences(): HasMany
    {
        return $this->hasMany(UserProximityPreference::class);
    }

    /**
     * Get the user's location history
     */
    public function locations(): HasMany
    {
        return $this->hasMany(UserLocation::class);
    }

    /**
     * Get the user's proximity alerts
     */
    public function proximityAlerts(): HasMany
    {
        return $this->hasMany(ProximityAlertLog::class);
    }

    /**
     * Get the user's QR codes
     */
    public function qrCodes(): HasMany
    {
        return $this->hasMany(QrCode::class);
    }

    /**
     * Get the user's transactions
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the user's favorites
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(UserFavorite::class);
    }

    /**
     * Get the user's reviews
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get the user's governorate
     */
    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    /**
     * Get the user's city
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Get the user's latest location
     */
    public function latestLocation()
    {
        return $this->hasOne(UserLocation::class)->latestOfMany('recorded_at');
    }

    /**
     * Check if user has active proximity preferences
     */
    public function hasActiveProximityAlerts(): bool
    {
        return $this->proximityPreferences()
            ->where('proximity_alerts_enabled', true)
            ->exists();
    }

    /**
     * Get user's preferred radius in meters
     */
    public function getProximityRadiusMeters(): int
    {
        $preference = $this->proximityPreferences()->first();
        return $preference ? $preference->proximity_radius_meters : 1000;
    }

    /**
     * Update FCM token
     */
    public function updateFcmToken(string $token): void
    {
        $this->update(['fcm_token' => $token]);
    }

    /**
     * Add points to user balance
     */
    public function addPoints(int $points): void
    {
        $this->increment('points_balance', $points);
        $this->checkAndUpdateLevel();
    }

    /**
     * Check and update user level based on points
     */
    protected function checkAndUpdateLevel(): void
    {
        $levels = [
            1 => 0,
            2 => 100,
            3 => 500,
            4 => 1000,
            5 => 2500,
            6 => 5000,
            7 => 10000,
        ];

        foreach (array_reverse($levels, true) as $level => $minPoints) {
            if ($this->points_balance >= $minPoints) {
                $this->update(['level' => $level]);
                break;
            }
        }
    }

    /**
     * Scope to get verified users
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope to get active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

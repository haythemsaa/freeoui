<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        // Loyalty program fields
        'loyalty_points_balance',
        'loyalty_points_lifetime',
        'loyalty_tier',
        // Referral program fields
        'referral_code',
        'referred_by_id',
        'successful_referrals',
        // Phase 4: Engagement fields
        'is_premium',
        'coins_balance',
        'checkin_streak',
        'last_checkin_date',
        'showcased_stickers',
        'wallet_balance',
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
        'is_premium' => 'boolean',
        'coins_balance' => 'integer',
        'checkin_streak' => 'integer',
        'last_checkin_date' => 'date',
        'showcased_stickers' => 'array',
        'wallet_balance' => 'decimal:2',
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
     * Get the user's loyalty points
     */
    public function loyaltyPoints(): HasMany
    {
        return $this->hasMany(LoyaltyPoint::class);
    }

    /**
     * Get the user's loyalty redemptions
     */
    public function loyaltyRedemptions(): HasMany
    {
        return $this->hasMany(LoyaltyRedemption::class);
    }

    /**
     * Get the user's bookings
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Get referrals made by this user
     */
    public function referralsGiven(): HasMany
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    /**
     * Get the referral received by this user
     */
    public function referralReceived(): HasOne
    {
        return $this->hasOne(Referral::class, 'referee_id');
    }

    /**
     * Get the user who referred this user
     */
    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by_id');
    }

    /**
     * Get users referred by this user
     */
    public function referredUsers(): HasMany
    {
        return $this->hasMany(User::class, 'referred_by_id');
    }

    /**
     * Get the user's achievements
     */
    public function achievements(): BelongsToMany
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')
            ->withPivot('progress', 'unlocked_at')
            ->withTimestamps();
    }

    /**
     * Get the user's achievement progress
     */
    public function userAchievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }

    /**
     * Get the user's review helpfulness votes
     */
    public function reviewHelpfulness(): HasMany
    {
        return $this->hasMany(ReviewHelpfulness::class);
    }

    /**
     * Get the user's wallet
     */
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * Get the user's wallet transactions
     */
    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * Get the user's payments
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the user's conversations
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * Get the user's notifications
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get the user's notification preferences
     */
    public function notificationPreferences(): HasOne
    {
        return $this->hasOne(NotificationPreference::class);
    }

    /**
     * Get the user's social shares
     */
    public function socialShares(): HasMany
    {
        return $this->hasMany(SocialShare::class);
    }

    /**
     * Get the user's analytics events
     */
    public function analyticsEvents(): HasMany
    {
        return $this->hasMany(AnalyticsEvent::class);
    }

    /**
     * Get the user's sessions
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(UserSession::class);
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
     * Get the user's subscriptions
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get the user's mayorships
     */
    public function mayorships(): HasMany
    {
        return $this->hasMany(Mayorship::class);
    }

    /**
     * Get the user's sticker collection
     */
    public function stickerCollection(): HasMany
    {
        return $this->hasMany(StickerCollection::class);
    }

    /**
     * Get the user's challenge participations
     */
    public function challengeParticipations(): HasMany
    {
        return $this->hasMany(ChallengeParticipation::class);
    }

    /**
     * Get the user's friends (many-to-many self-referencing)
     */
    public function friends(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_friends', 'user_id', 'friend_id')
            ->withTimestamps();
    }

    /**
     * Get the user's stories
     */
    public function stories(): HasMany
    {
        return $this->hasMany(Story::class);
    }

    /**
     * Get the user's story views
     */
    public function storyViews(): HasMany
    {
        return $this->hasMany(StoryView::class);
    }

    /**
     * Get the user's cashbacks
     */
    public function cashbacks(): HasMany
    {
        return $this->hasMany(Cashback::class);
    }

    /**
     * Get purchased gift cards
     */
    public function purchasedGiftCards(): HasMany
    {
        return $this->hasMany(GiftCard::class, 'buyer_id');
    }

    /**
     * Get redeemed gift cards
     */
    public function redeemedGiftCards(): HasMany
    {
        return $this->hasMany(GiftCard::class, 'redeemed_by_id');
    }

    /**
     * Get followed merchants
     */
    public function followedMerchants(): BelongsToMany
    {
        return $this->belongsToMany(Merchant::class, 'merchant_followers')
            ->withTimestamps();
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

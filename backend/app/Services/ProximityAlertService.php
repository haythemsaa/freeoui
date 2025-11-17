<?php

namespace App\Services;

use App\Models\Advantage;
use App\Models\Merchant;
use App\Models\ProximityAlertLog;
use App\Models\User;
use App\Models\UserLocation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProximityAlertService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Process proximity detection for a user location update
     */
    public function processLocationUpdate(User $user, float $latitude, float $longitude): void
    {
        // Check if user has proximity alerts enabled
        if (!$user->hasActiveProximityAlerts()) {
            return;
        }

        $preferences = $user->proximityPreferences()->first();

        // Check quiet hours
        if ($preferences && $preferences->isInQuietHours()) {
            return;
        }

        // Check daily alert limit
        if (!$this->canSendAlertToUser($user, $preferences)) {
            return;
        }

        // Find nearby advantages
        $nearbyAdvantages = $this->findNearbyAdvantages(
            $user,
            $latitude,
            $longitude,
            $preferences->proximity_radius_meters ?? 1000
        );

        // Process each nearby advantage
        foreach ($nearbyAdvantages as $advantage) {
            $this->processNearbyAdvantage($user, $advantage, $latitude, $longitude);
        }
    }

    /**
     * Find advantages near a location
     */
    protected function findNearbyAdvantages(
        User $user,
        float $latitude,
        float $longitude,
        int $radiusMeters
    ): Collection {
        $preferences = $user->proximityPreferences()->first();

        $query = Advantage::query()
            ->with(['merchant', 'category'])
            ->join('merchants', 'advantages.merchant_id', '=', 'merchants.id')
            ->select('advantages.*')
            ->selectRaw(
                "ST_Distance(
                    ST_MakePoint(?, ?)::geography,
                    ST_MakePoint(merchants.longitude, merchants.latitude)::geography
                ) as distance_meters",
                [$longitude, $latitude]
            )
            ->where('advantages.status', 'active')
            ->where('advantages.start_date', '<=', now())
            ->where('advantages.end_date', '>=', now())
            ->where('merchants.status', 'active')
            ->whereRaw(
                "ST_DWithin(
                    ST_MakePoint(?, ?)::geography,
                    ST_MakePoint(merchants.longitude, merchants.latitude)::geography,
                    ?
                )",
                [$longitude, $latitude, $radiusMeters]
            );

        // Filter by categories if set
        if ($preferences && !empty($preferences->interested_category_ids)) {
            $query->whereIn('advantages.category_id', $preferences->interested_category_ids);
        }

        // Filter by minimum discount if set
        if ($preferences && $preferences->minimum_discount_percentage) {
            $query->where(function ($q) use ($preferences) {
                $q->where('advantages.discount_percentage', '>=', $preferences->minimum_discount_percentage)
                    ->orWhereNull('advantages.discount_percentage');
            });
        }

        // Filter featured only if set
        if ($preferences && $preferences->notify_for_featured_only) {
            $query->where('advantages.is_featured', true);
        }

        return $query->orderBy('distance_meters')->limit(10)->get();
    }

    /**
     * Process a nearby advantage for alert
     */
    protected function processNearbyAdvantage(
        User $user,
        Advantage $advantage,
        float $latitude,
        float $longitude
    ): void {
        // Check if should send alert (anti-spam rules)
        if (!$this->shouldSendAlert($user, $advantage)) {
            return;
        }

        // Check if merchant can still send alerts (quota)
        if (!$advantage->merchant->canSendAlert()) {
            return;
        }

        // Calculate relevance score
        $relevanceScore = $this->calculateRelevanceScore($advantage, $latitude, $longitude);

        // Create alert log
        $alert = ProximityAlertLog::create([
            'user_id' => $user->id,
            'advantage_id' => $advantage->id,
            'merchant_id' => $advantage->merchant_id,
            'user_latitude' => $latitude,
            'user_longitude' => $longitude,
            'distance_meters' => $advantage->distance_meters ?? 0,
            'relevance_score' => $relevanceScore,
            'time_of_day' => $this->getTimeOfDay(),
            'day_of_week' => now()->dayOfWeekIso,
        ]);

        // Send push notification
        $this->sendProximityNotification($user, $advantage, $alert);

        // Increment counters
        $advantage->incrementProximityAlertsSent();
    }

    /**
     * Check if alert should be sent (anti-spam rules)
     */
    protected function shouldSendAlert(User $user, Advantage $advantage): bool
    {
        $preferences = $user->proximityPreferences()->first();

        // Rule 1: Check if user already used this advantage
        if ($advantage->transactions()->where('user_id', $user->id)->exists()) {
            return false;
        }

        // Rule 2: Check if already alerted for same merchant today
        $alertedToday = ProximityAlertLog::where('user_id', $user->id)
            ->where('merchant_id', $advantage->merchant_id)
            ->whereDate('created_at', today())
            ->exists();

        if ($alertedToday) {
            return false;
        }

        // Rule 3: Check minimum interval between alerts
        $minInterval = $preferences->min_notification_interval_minutes ?? 30;
        $lastAlert = ProximityAlertLog::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subMinutes($minInterval))
            ->exists();

        if ($lastAlert) {
            return false;
        }

        return true;
    }

    /**
     * Check if user can receive more alerts today
     */
    protected function canSendAlertToUser(User $user, $preferences): bool
    {
        $maxDaily = $preferences->max_daily_notifications ?? 5;

        $todayCount = ProximityAlertLog::where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->count();

        return $todayCount < $maxDaily;
    }

    /**
     * Calculate relevance score for an advantage
     */
    protected function calculateRelevanceScore(Advantage $advantage, float $userLat, float $userLon): float
    {
        $distance = $advantage->distance_meters ?? 1000;
        $discount = $advantage->discount_percentage ?? 0;
        $uses = $advantage->uses_count ?? 0;
        $rating = $advantage->average_rating ?? 0;
        $expiresSoon = $advantage->end_date->diffInHours(now()) < 24;

        // Proximity score (max 30)
        $proximityScore = max(0, (1000 - min($distance, 1000)) / 1000 * 30);

        // Discount score (max 50)
        $discountScore = min($discount * 0.5, 50);

        // Popularity score (max 20)
        $popularityScore = min($uses / 100, 20);

        // Quality score (max 25)
        $qualityScore = $rating * 5;

        // Urgency bonus (15)
        $urgencyBonus = $expiresSoon ? 15 : 0;

        return round($proximityScore + $discountScore + $popularityScore + $qualityScore + $urgencyBonus, 2);
    }

    /**
     * Send proximity push notification
     */
    protected function sendProximityNotification(User $user, Advantage $advantage, ProximityAlertLog $alert): void
    {
        try {
            $distance = round($advantage->distance_meters);

            $title = '🎁 Nouvelle offre à ' . $distance . 'm !';
            $body = "🏪 {$advantage->merchant->business_name}\n{$advantage->getDiscountDisplay()}";

            $fcmMessageId = $this->notificationService->sendPushNotification(
                $user->fcm_token,
                $title,
                $body,
                [
                    'type' => 'proximity_alert',
                    'alert_id' => $alert->id,
                    'advantage_id' => $advantage->id,
                    'merchant_id' => $advantage->merchant_id,
                ]
            );

            // Update alert log
            $alert->update([
                'notification_sent' => true,
                'notification_sent_at' => now(),
                'notification_fcm_id' => $fcmMessageId,
            ]);

            Log::info('Proximity alert sent', [
                'user_id' => $user->id,
                'advantage_id' => $advantage->id,
                'distance' => $distance,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send proximity alert', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'advantage_id' => $advantage->id,
            ]);
        }
    }

    /**
     * Get time of day category
     */
    protected function getTimeOfDay(): string
    {
        $hour = now()->hour;

        return match (true) {
            $hour >= 6 && $hour < 12 => 'morning',
            $hour >= 12 && $hour < 18 => 'afternoon',
            $hour >= 18 && $hour < 22 => 'evening',
            default => 'night',
        };
    }

    /**
     * Get analytics for merchant
     */
    public function getMerchantAnalytics(Merchant $merchant, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $alerts = ProximityAlertLog::where('merchant_id', $merchant->id)
            ->where('created_at', '>=', $startDate)
            ->get();

        $totalSent = $alerts->count();
        $totalOpened = $alerts->where('notification_opened', true)->count();
        $totalViewed = $alerts->where('advantage_viewed', true)->count();
        $totalUsed = $alerts->where('advantage_used', true)->count();

        return [
            'total_alerts_sent' => $totalSent,
            'total_alerts_opened' => $totalOpened,
            'total_advantages_viewed' => $totalViewed,
            'total_advantages_used' => $totalUsed,
            'open_rate' => $totalSent > 0 ? round(($totalOpened / $totalSent) * 100, 2) : 0,
            'view_rate' => $totalOpened > 0 ? round(($totalViewed / $totalOpened) * 100, 2) : 0,
            'conversion_rate' => $totalSent > 0 ? round(($totalUsed / $totalSent) * 100, 2) : 0,
        ];
    }
}

<?php

namespace App\Services;

use App\Models\User;
use App\Models\Advantage;
use App\Models\Merchant;
use App\Jobs\SendPushNotificationJob;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class SmartNotificationService
{
    public function __construct(
        private NotificationService $notificationService,
        private RecommendationService $recommendationService
    ) {}

    /**
     * Send smart personalized notifications
     */
    public function sendPersonalizedNotifications(): int
    {
        $users = User::where('is_active', true)
            ->where('push_notifications', true)
            ->whereNotNull('fcm_token')
            ->get();

        $sent = 0;

        foreach ($users as $user) {
            // Avoid notification fatigue (max 3 per day)
            if ($this->shouldSendNotification($user)) {
                $this->sendSmartNotification($user);
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Check if we should send notification to user
     */
    private function shouldSendNotification(User $user): bool
    {
        $todayKey = "notifications:sent:{$user->id}:" . now()->format('Y-m-d');
        $sentToday = Cache::get($todayKey, 0);

        // Max 3 notifications per day
        if ($sentToday >= 3) {
            return false;
        }

        // Respect user's quiet hours (22h - 8h)
        $currentHour = now()->hour;
        if ($currentHour >= 22 || $currentHour < 8) {
            return false;
        }

        // Don't send if user was recently active (< 1 hour ago)
        if ($user->last_login_at && $user->last_login_at->diffInHours(now()) < 1) {
            return false;
        }

        return true;
    }

    /**
     * Send smart notification based on user behavior
     */
    private function sendSmartNotification(User $user): void
    {
        $notificationType = $this->determineNotificationType($user);

        switch ($notificationType) {
            case 'recommendation':
                $this->sendRecommendationNotification($user);
                break;

            case 'nearby':
                $this->sendNearbyNotification($user);
                break;

            case 'ending_soon':
                $this->sendEndingSoonNotification($user);
                break;

            case 'reward':
                $this->sendRewardNotification($user);
                break;

            case 'inactive':
                $this->sendReengagementNotification($user);
                break;

            case 'streak':
                $this->sendStreakNotification($user);
                break;
        }

        $this->incrementNotificationCount($user);
    }

    /**
     * Determine which type of notification to send
     */
    private function determineNotificationType(User $user): string
    {
        // Priority 1: User inactive for 7+ days
        if ($user->last_login_at && $user->last_login_at->diffInDays(now()) >= 7) {
            return 'inactive';
        }

        // Priority 2: User has a streak to maintain
        if ($user->checkin_streak >= 3) {
            $lastCheckin = $user->last_checkin_date ? Carbon::parse($user->last_checkin_date) : null;
            if ($lastCheckin && $lastCheckin->isYesterday()) {
                return 'streak';
            }
        }

        // Priority 3: User has favorites ending soon
        $endingSoon = $user->favorites()
            ->whereHas('advantage', function ($query) {
                $query->where('ends_at', '>', now())
                    ->where('ends_at', '<=', now()->addDays(2));
            })
            ->exists();

        if ($endingSoon) {
            return 'ending_soon';
        }

        // Priority 4: New nearby advantages
        $latestLocation = $user->latestLocation;
        if ($latestLocation && now()->diffInHours($latestLocation->recorded_at) < 24) {
            return 'nearby';
        }

        // Priority 5: Level up or achievement
        if ($user->points_balance > 0 && $user->level < 7) {
            return 'reward';
        }

        // Default: Personalized recommendation
        return 'recommendation';
    }

    /**
     * Send AI-powered recommendation notification
     */
    private function sendRecommendationNotification(User $user): void
    {
        $recommendations = $this->recommendationService->getPersonalizedRecommendations($user, 1);

        if ($recommendations->isEmpty()) {
            return;
        }

        $advantage = $recommendations->first();

        $this->notificationService->send(
            $user,
            '✨ Recommandation personnalisée',
            "{$advantage->title} - {$advantage->discount_percentage}% de réduction chez {$advantage->merchant->name}",
            'recommendation',
            [
                'advantage_id' => $advantage->id,
                'merchant_id' => $advantage->merchant_id,
                'reason' => $advantage->recommendation_reason ?? 'Pour vous',
            ]
        );
    }

    /**
     * Send nearby advantage notification
     */
    private function sendNearbyNotification(User $user): void
    {
        $latestLocation = $user->latestLocation;

        if (!$latestLocation) {
            return;
        }

        $nearbyMerchants = Merchant::selectRaw(
            "*, ST_Distance_Sphere(
                point(longitude, latitude),
                point(?, ?)
            ) as distance",
            [$latestLocation->longitude, $latestLocation->latitude]
        )
            ->has('advantages')
            ->having('distance', '<', 1000)
            ->orderBy('distance')
            ->first();

        if (!$nearbyMerchants) {
            return;
        }

        $this->notificationService->send(
            $user,
            '📍 Avantage à proximité !',
            "{$nearbyMerchants->name} est à moins de 1km. Découvrez leurs offres !",
            'proximity',
            ['merchant_id' => $nearbyMerchants->id]
        );
    }

    /**
     * Send ending soon notification
     */
    private function sendEndingSoonNotification(User $user): void
    {
        $endingSoon = $user->favorites()
            ->with('advantage.merchant')
            ->whereHas('advantage', function ($query) {
                $query->where('ends_at', '>', now())
                    ->where('ends_at', '<=', now()->addDays(2));
            })
            ->first();

        if (!$endingSoon) {
            return;
        }

        $advantage = $endingSoon->advantage;
        $hoursLeft = now()->diffInHours($advantage->ends_at);

        $this->notificationService->send(
            $user,
            '⏰ L\'offre se termine bientôt !',
            "{$advantage->title} chez {$advantage->merchant->name} - Plus que {$hoursLeft}h !",
            'urgency',
            [
                'advantage_id' => $advantage->id,
                'hours_remaining' => $hoursLeft,
            ],
            'high'
        );
    }

    /**
     * Send reward/achievement notification
     */
    private function sendRewardNotification(User $user): void
    {
        $pointsToNextLevel = $this->getPointsToNextLevel($user);

        if ($pointsToNextLevel === null || $pointsToNextLevel > 500) {
            return;
        }

        $this->notificationService->send(
            $user,
            '🎯 Presque au niveau suivant !',
            "Plus que {$pointsToNextLevel} points pour atteindre le niveau " . ($user->level + 1) . " !",
            'achievement',
            ['points_needed' => $pointsToNextLevel]
        );
    }

    /**
     * Send re-engagement notification for inactive users
     */
    private function sendReengagementNotification(User $user): void
    {
        $daysInactive = $user->last_login_at ? $user->last_login_at->diffInDays(now()) : 0;

        $messages = [
            "On vous a manqué ! {$user->first_name}, découvrez les nouvelles offres",
            "🎁 Offres spéciales vous attendent !",
            "Vos favoris vous attendent ! Revenez vite",
        ];

        $this->notificationService->send(
            $user,
            '👋 Vous nous manquez !',
            $messages[array_rand($messages)],
            'reengagement',
            ['days_inactive' => $daysInactive]
        );
    }

    /**
     * Send streak maintenance notification
     */
    private function sendStreakNotification(User $user): void
    {
        $this->notificationService->send(
            $user,
            '🔥 Maintenez votre série !',
            "Vous avez une série de {$user->checkin_streak} jours ! Ne la perdez pas aujourd'hui.",
            'streak',
            [
                'streak_count' => $user->checkin_streak,
                'streak_reward' => $user->checkin_streak * 10,
            ]
        );
    }

    /**
     * Get points needed to reach next level
     */
    private function getPointsToNextLevel(User $user): ?int
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

        $nextLevel = $user->level + 1;

        if (!isset($levels[$nextLevel])) {
            return null;
        }

        return $levels[$nextLevel] - $user->points_balance;
    }

    /**
     * Increment notification count for user
     */
    private function incrementNotificationCount(User $user): void
    {
        $todayKey = "notifications:sent:{$user->id}:" . now()->format('Y-m-d');
        $current = Cache::get($todayKey, 0);
        Cache::put($todayKey, $current + 1, now()->endOfDay());
    }

    /**
     * Send cart abandonment notification
     */
    public function sendCartAbandonmentNotification(User $user, array $cartItems): void
    {
        if (empty($cartItems)) {
            return;
        }

        $this->notificationService->send(
            $user,
            '🛒 Vous avez oublié quelque chose !',
            "Votre panier contient " . count($cartItems) . " article(s). Finalisez votre achat maintenant !",
            'cart_abandonment',
            ['cart_count' => count($cartItems)]
        );
    }

    /**
     * Send price drop notification
     */
    public function sendPriceDropNotification(User $user, Advantage $advantage, float $oldPrice, float $newPrice): void
    {
        $savings = $oldPrice - $newPrice;

        $this->notificationService->send(
            $user,
            '💰 Baisse de prix !',
            "{$advantage->title} - Économisez {$savings} TND maintenant !",
            'price_drop',
            [
                'advantage_id' => $advantage->id,
                'old_price' => $oldPrice,
                'new_price' => $newPrice,
                'savings' => $savings,
            ],
            'high'
        );
    }

    /**
     * Send birthday notification
     */
    public function sendBirthdayNotifications(): int
    {
        $birthdayUsers = User::whereMonth('date_of_birth', now()->month)
            ->whereDay('date_of_birth', now()->day)
            ->where('is_active', true)
            ->get();

        foreach ($birthdayUsers as $user) {
            $this->notificationService->send(
                $user,
                '🎂 Joyeux anniversaire !',
                "Joyeux anniversaire {$user->first_name} ! Profitez de 500 points bonus offerts !",
                'birthday',
                ['bonus_points' => 500],
                'high'
            );

            // Award birthday bonus
            $user->increment('points_balance', 500);
        }

        return $birthdayUsers->count();
    }
}

<?php

namespace App\Services;

use App\Models\Mayorship;
use App\Models\Merchant;
use App\Models\User;
use App\Services\NotificationService;

class MayorshipService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Record check-in and update mayorship
     */
    public function recordCheckin(User $user, Merchant $merchant): array
    {
        // Update or create user's mayorship record
        $mayorship = Mayorship::firstOrCreate(
            [
                'user_id' => $user->id,
                'merchant_id' => $merchant->id,
            ],
            [
                'checkin_count' => 1,
                'claimed_at' => now(),
                'last_checkin_at' => now(),
                'is_active' => false,
            ]
        );

        // Only count one check-in per day
        if ($mayorship->last_checkin_at->isToday()) {
            return [
                'is_mayor' => $mayorship->is_active,
                'checkin_count' => $mayorship->checkin_count,
                'current_mayor' => $this->getCurrentMayor($merchant),
            ];
        }

        $mayorship->incrementCheckin();

        // Update streak
        $this->updateStreak($user);

        // Check if user should become mayor
        $currentMayor = $this->getCurrentMayor($merchant);
        $shouldBecomeMayor = false;

        if (!$currentMayor || $mayorship->checkin_count > $currentMayor->checkin_count) {
            $this->transferMayorship($mayorship, $currentMayor);
            $shouldBecomeMayor = true;
        }

        // Award coins (with bonus if mayor)
        $coinsAwarded = $this->awardCoins($user, $mayorship->is_active);

        return [
            'is_mayor' => $mayorship->is_active,
            'became_mayor' => $shouldBecomeMayor,
            'checkin_count' => $mayorship->checkin_count,
            'coins_awarded' => $coinsAwarded,
            'current_mayor' => $this->getCurrentMayor($merchant),
        ];
    }

    /**
     * Get current mayor for merchant
     */
    public function getCurrentMayor(Merchant $merchant): ?Mayorship
    {
        return Mayorship::where('merchant_id', $merchant->id)
            ->where('is_active', true)
            ->where('last_checkin_at', '>', now()->subDays(30))
            ->first();
    }

    /**
     * Get challengers for a mayorship
     */
    public function getChallengers(Merchant $merchant, int $limit = 5): \Illuminate\Support\Collection
    {
        return Mayorship::where('merchant_id', $merchant->id)
            ->where('last_checkin_at', '>', now()->subDays(30))
            ->orderByDesc('checkin_count')
            ->with('user')
            ->limit($limit)
            ->get();
    }

    /**
     * Transfer mayorship
     */
    private function transferMayorship(Mayorship $newMayor, ?Mayorship $oldMayor): void
    {
        if ($oldMayor) {
            $oldMayor->update(['is_active' => false]);
            
            // Notify old mayor
            $this->notificationService->send(
                $oldMayor->user,
                '👑 Mayorship perdu !',
                "Vous n'êtes plus maire de {$newMayor->merchant->name}",
                'mayorship',
                ['merchant_id' => $newMayor->merchant_id]
            );
        }

        $newMayor->update([
            'is_active' => true,
            'claimed_at' => now(),
        ]);

        // Notify new mayor
        $this->notificationService->send(
            $newMayor->user,
            '🎉 Nouveau maire !',
            "Félicitations ! Vous êtes le nouveau maire de {$newMayor->merchant->name}",
            'mayorship',
            ['merchant_id' => $newMayor->merchant_id],
            'high'
        );

        // Award bonus points
        $newMayor->user->increment('points_balance', 500);
    }

    /**
     * Award coins for check-in
     */
    private function awardCoins(User $user, bool $isMayor): int
    {
        $baseCoins = 10;
        $mayorBonus = $isMayor ? 1.5 : 1.0;
        
        $coinsToAward = (int) ($baseCoins * $mayorBonus);
        
        $user->increment('coins_balance', $coinsToAward);
        
        return $coinsToAward;
    }

    /**
     * Update user streak
     */
    private function updateStreak(User $user): void
    {
        if (!$user->last_checkin_date) {
            $user->update([
                'checkin_streak' => 1,
                'last_checkin_date' => now()->toDateString(),
            ]);
            return;
        }

        $lastCheckin = \Carbon\Carbon::parse($user->last_checkin_date);
        
        if ($lastCheckin->isYesterday()) {
            // Continue streak
            $user->increment('checkin_streak');
        } elseif (!$lastCheckin->isToday()) {
            // Reset streak
            $user->update(['checkin_streak' => 1]);
        }

        $user->update(['last_checkin_date' => now()->toDateString()]);
    }

    /**
     * Clean up expired mayorships
     */
    public function cleanupExpired(): int
    {
        return Mayorship::where('is_active', true)
            ->where('last_checkin_at', '<', now()->subDays(30))
            ->update(['is_active' => false]);
    }
}

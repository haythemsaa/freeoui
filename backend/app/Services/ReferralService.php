<?php

namespace App\Services;

use App\Models\User;
use App\Models\Referral;
use Illuminate\Support\Str;

class ReferralService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Generate unique referral code for user
     */
    public function generateReferralCode(User $user): string
    {
        if ($user->referral_code) {
            return $user->referral_code;
        }

        $code = strtoupper(Str::random(6));

        // Ensure uniqueness
        while (User::where('referral_code', $code)->exists()) {
            $code = strtoupper(Str::random(6));
        }

        $user->update(['referral_code' => $code]);

        return $code;
    }

    /**
     * Process referral when new user signs up with code
     */
    public function processReferral(User $newUser, string $referralCode): ?Referral
    {
        $referrer = User::where('referral_code', $referralCode)->first();

        if (!$referrer) {
            return null;
        }

        // Create referral record
        $referral = Referral::create([
            'referrer_id' => $referrer->id,
            'referee_id' => $newUser->id,
            'referral_code' => $referralCode,
            'status' => 'pending',
            'tier' => 1,
        ]);

        // Update user's referred_by
        $newUser->update(['referred_by_id' => $referrer->id]);

        // Send notification to referrer
        $this->notificationService->send(
            $referrer,
            '🎉 Nouveau filleul !',
            "{$newUser->first_name} vient de s'inscrire avec votre code !",
            'referral',
            ['referee_id' => $newUser->id]
        );

        return $referral;
    }

    /**
     * Activate referral and award rewards (when referee makes first purchase)
     */
    public function activateReferral(Referral $referral): void
    {
        if ($referral->status !== 'pending') {
            return;
        }

        $referral->update([
            'status' => 'active',
            'activated_at' => now(),
        ]);

        // Multi-tier rewards
        $this->awardReferralRewards($referral);

        // Increment successful referrals count
        $referral->referrer->increment('successful_referrals');

        // Check tier upgrade
        $this->checkTierUpgrade($referral->referrer);
    }

    /**
     * Award multi-tier referral rewards
     */
    private function awardReferralRewards(Referral $referral): void
    {
        $tiers = [
            1 => ['referrer_points' => 500, 'referee_points' => 200],
            2 => ['referrer_points' => 750, 'referee_points' => 300],
            3 => ['referrer_points' => 1000, 'referee_points' => 400],
        ];

        $tier = min($referral->referrer->successful_referrals + 1, 3);
        $rewards = $tiers[$tier];

        // Reward referrer
        $referral->referrer->increment('points_balance', $rewards['referrer_points']);
        $referral->referrer->increment('coins_balance', $rewards['referrer_points'] / 10);

        $this->notificationService->send(
            $referral->referrer,
            '💰 Récompense de parrainage !',
            "Vous avez gagné {$rewards['referrer_points']} points pour le parrainage de {$referral->referee->first_name} !",
            'referral_reward',
            ['points' => $rewards['referrer_points']],
            'high'
        );

        // Reward referee
        $referral->referee->increment('points_balance', $rewards['referee_points']);
        $referral->referee->increment('coins_balance', $rewards['referee_points'] / 10);

        $this->notificationService->send(
            $referral->referee,
            '🎁 Bonus de bienvenue !',
            "Vous avez gagné {$rewards['referee_points']} points grâce au parrainage de {$referral->referrer->first_name} !",
            'referral_bonus',
            ['points' => $rewards['referee_points']]
        );

        // Update referral tier and rewards
        $referral->update([
            'tier' => $tier,
            'referrer_reward' => $rewards['referrer_points'],
            'referee_reward' => $rewards['referee_points'],
        ]);
    }

    /**
     * Check and upgrade referral tier
     */
    private function checkTierUpgrade(User $user): void
    {
        $successfulReferrals = $user->successful_referrals;

        $newTier = match(true) {
            $successfulReferrals >= 50 => 'diamond',
            $successfulReferrals >= 20 => 'gold',
            $successfulReferrals >= 10 => 'silver',
            $successfulReferrals >= 5 => 'bronze',
            default => null,
        };

        if ($newTier && $user->referral_tier !== $newTier) {
            $user->update(['referral_tier' => $newTier]);

            $this->notificationService->send(
                $user,
                "🏆 Niveau de parrainage {$newTier} !",
                "Félicitations ! Vous êtes maintenant un parrain {$newTier}. Continuez comme ça !",
                'tier_upgrade',
                ['tier' => $newTier],
                'high'
            );
        }
    }

    /**
     * Get referral statistics for user
     */
    public function getReferralStats(User $user): array
    {
        $referrals = Referral::where('referrer_id', $user->id)->get();

        return [
            'total_referrals' => $referrals->count(),
            'active_referrals' => $referrals->where('status', 'active')->count(),
            'pending_referrals' => $referrals->where('status', 'pending')->count(),
            'total_points_earned' => $referrals->sum('referrer_reward'),
            'referral_tier' => $user->referral_tier ?? 'none',
            'next_tier_in' => $this->getReferralsToNextTier($user),
            'referral_code' => $user->referral_code,
        ];
    }

    /**
     * Get number of referrals needed for next tier
     */
    private function getReferralsToNextTier(User $user): ?int
    {
        $current = $user->successful_referrals;

        $nextMilestone = match(true) {
            $current < 5 => 5,
            $current < 10 => 10,
            $current < 20 => 20,
            $current < 50 => 50,
            default => null,
        };

        return $nextMilestone ? $nextMilestone - $current : null;
    }

    /**
     * Get referral leaderboard
     */
    public function getReferralLeaderboard(int $limit = 50): \Illuminate\Support\Collection
    {
        return User::where('successful_referrals', '>', 0)
            ->orderByDesc('successful_referrals')
            ->limit($limit)
            ->get(['id', 'first_name', 'last_name', 'avatar_url', 'successful_referrals', 'referral_tier'])
            ->map(function ($user, $index) {
                return [
                    'rank' => $index + 1,
                    'user' => $user,
                    'referrals' => $user->successful_referrals,
                    'tier' => $user->referral_tier,
                ];
            });
    }
}

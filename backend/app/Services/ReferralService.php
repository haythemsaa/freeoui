<?php

namespace App\Services;

use App\Models\User;
use App\Models\Referral;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReferralService
{
    // Default reward configuration (can be moved to config file)
    protected const REFERRER_POINTS = 500;
    protected const REFEREE_POINTS = 300;
    protected const REFERRER_AMOUNT = 5.000; // TND
    protected const REFEREE_AMOUNT = 3.000; // TND
    protected const MIN_PURCHASE_AMOUNT = 20.000; // TND

    /**
     * Generate referral code for user
     */
    public function generateReferralCode(User $user): string
    {
        if ($user->referral_code) {
            return $user->referral_code;
        }

        do {
            $code = strtoupper(Str::random(8));
        } while (User::where('referral_code', $code)->exists());

        $user->update(['referral_code' => $code]);

        return $code;
    }

    /**
     * Register a referral (when someone signs up with referral code)
     */
    public function registerReferral(User $referee, string $referralCode): Referral
    {
        // Find referrer
        $referrer = User::where('referral_code', $referralCode)->first();

        if (!$referrer) {
            throw new \Exception('Invalid referral code');
        }

        if ($referrer->id === $referee->id) {
            throw new \Exception('You cannot refer yourself');
        }

        if ($referee->referred_by_id) {
            throw new \Exception('This user was already referred');
        }

        return DB::transaction(function () use ($referrer, $referee) {
            // Create referral record
            $referral = Referral::create([
                'referrer_id' => $referrer->id,
                'referee_id' => $referee->id,
                'status' => 'pending',
                'referrer_reward_points' => self::REFERRER_POINTS,
                'referee_reward_points' => self::REFEREE_POINTS,
                'referrer_reward_amount' => self::REFERRER_AMOUNT,
                'referee_reward_amount' => self::REFEREE_AMOUNT,
            ]);

            // Update referee
            $referee->update(['referred_by_id' => $referrer->id]);

            // Award welcome bonus to referee immediately
            if (app()->has(LoyaltyService::class)) {
                app(LoyaltyService::class)->awardBonusPoints(
                    $referee,
                    self::REFEREE_POINTS,
                    'Referral welcome bonus'
                );
            }

            return $referral;
        });
    }

    /**
     * Complete a referral (when referee makes qualifying purchase)
     */
    public function completeReferral(User $referee, float $purchaseAmount): ?Referral
    {
        if (!$referee->referred_by_id) {
            return null;
        }

        // Find pending referral
        $referral = Referral::where('referee_id', $referee->id)
            ->pending()
            ->first();

        if (!$referral) {
            return null;
        }

        // Check if purchase meets minimum
        if ($purchaseAmount < self::MIN_PURCHASE_AMOUNT) {
            return null;
        }

        return DB::transaction(function () use ($referral, $referee) {
            $referral->markAsCompleted();

            // Award points/rewards to both users
            if (app()->has(LoyaltyService::class)) {
                $loyaltyService = app(LoyaltyService::class);

                // Reward referrer
                $loyaltyService->awardBonusPoints(
                    $referral->referrer,
                    $referral->referrer_reward_points,
                    'Referral reward: ' . $referee->name
                );

                // Additional reward for referee
                $loyaltyService->awardBonusPoints(
                    $referee,
                    50,
                    'Completed first qualifying purchase'
                );
            }

            // Update referrer's successful referrals count
            $referral->referrer->increment('successful_referrals');

            $referral->markAsRewarded();

            return $referral;
        });
    }

    /**
     * Get referral statistics for user
     */
    public function getReferralStats(User $user): array
    {
        $referrals = Referral::where('referrer_id', $user->id)->get();

        return [
            'total_referrals' => $referrals->count(),
            'pending_referrals' => $referrals->where('status', 'pending')->count(),
            'completed_referrals' => $referrals->where('status', 'completed')->count(),
            'rewarded_referrals' => $referrals->where('status', 'rewarded')->count(),
            'total_points_earned' => $referrals->where('status', 'rewarded')->sum('referrer_reward_points'),
            'total_amount_earned' => $referrals->where('status', 'rewarded')->sum('referrer_reward_amount'),
            'referral_code' => $user->referral_code ?? $this->generateReferralCode($user),
            'referral_link' => config('app.url') . '/register?ref=' . ($user->referral_code ?? ''),
        ];
    }

    /**
     * Get leaderboard of top referrers
     */
    public function getLeaderboard(int $limit = 10): array
    {
        return User::where('successful_referrals', '>', 0)
            ->orderByDesc('successful_referrals')
            ->limit($limit)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'successful_referrals' => $user->successful_referrals,
                    'loyalty_tier' => $user->loyalty_tier,
                ];
            })
            ->toArray();
    }

    /**
     * Validate referral code
     */
    public function validateReferralCode(string $code): bool
    {
        return User::where('referral_code', $code)->exists();
    }

    /**
     * Get referral by code
     */
    public function getReferralByCode(string $code): ?User
    {
        return User::where('referral_code', $code)->first();
    }
}

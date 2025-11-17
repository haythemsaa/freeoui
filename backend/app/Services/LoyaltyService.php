<?php

namespace App\Services;

use App\Models\User;
use App\Models\LoyaltyPoint;
use App\Models\LoyaltyReward;
use App\Models\LoyaltyRedemption;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LoyaltyService
{
    /**
     * Award points to a user
     */
    public function awardPoints(
        User $user,
        int $points,
        string $description,
        ?Model $relatedModel = null,
        ?\DateTimeInterface $expiresAt = null
    ): LoyaltyPoint {
        return DB::transaction(function () use ($user, $points, $description, $relatedModel, $expiresAt) {
            // Create points record
            $loyaltyPoint = LoyaltyPoint::create([
                'user_id' => $user->id,
                'points' => $points,
                'type' => 'earned',
                'description' => $description,
                'pointable_type' => $relatedModel ? get_class($relatedModel) : null,
                'pointable_id' => $relatedModel?->id,
                'expires_at' => $expiresAt,
            ]);

            // Update user balance
            $user->increment('loyalty_points_balance', $points);
            $user->increment('loyalty_points_lifetime', $points);

            // Check for tier upgrade
            $this->updateUserTier($user);

            return $loyaltyPoint;
        });
    }

    /**
     * Deduct points from a user
     */
    public function deductPoints(
        User $user,
        int $points,
        string $description,
        ?Model $relatedModel = null
    ): LoyaltyPoint {
        if ($user->loyalty_points_balance < $points) {
            throw new \Exception('Insufficient points balance');
        }

        return DB::transaction(function () use ($user, $points, $description, $relatedModel) {
            $loyaltyPoint = LoyaltyPoint::create([
                'user_id' => $user->id,
                'points' => -$points,
                'type' => 'redeemed',
                'description' => $description,
                'pointable_type' => $relatedModel ? get_class($relatedModel) : null,
                'pointable_id' => $relatedModel?->id,
            ]);

            $user->decrement('loyalty_points_balance', $points);

            return $loyaltyPoint;
        });
    }

    /**
     * Award bonus points
     */
    public function awardBonusPoints(
        User $user,
        int $points,
        string $description
    ): LoyaltyPoint {
        return DB::transaction(function () use ($user, $points, $description) {
            $loyaltyPoint = LoyaltyPoint::create([
                'user_id' => $user->id,
                'points' => $points,
                'type' => 'bonus',
                'description' => $description,
            ]);

            $user->increment('loyalty_points_balance', $points);
            $user->increment('loyalty_points_lifetime', $points);

            return $loyaltyPoint;
        });
    }

    /**
     * Redeem a reward
     */
    public function redeemReward(User $user, LoyaltyReward $reward): LoyaltyRedemption
    {
        if (!$reward->canUserRedeem($user)) {
            throw new \Exception('Cannot redeem this reward');
        }

        return DB::transaction(function () use ($user, $reward) {
            // Deduct points
            $this->deductPoints(
                $user,
                $reward->points_required,
                "Redeemed: {$reward->name}",
                $reward
            );

            // Create redemption
            $redemption = LoyaltyRedemption::create([
                'user_id' => $user->id,
                'reward_id' => $reward->id,
                'points_spent' => $reward->points_required,
                'status' => 'pending',
                'expires_at' => now()->addDays(30), // Redemption code valid for 30 days
            ]);

            // Decrement stock
            $reward->decrementStock();

            return $redemption;
        });
    }

    /**
     * Expire old points
     */
    public function expirePoints(): int
    {
        $expiredPoints = LoyaltyPoint::where('type', 'earned')
            ->where('expires_at', '<=', now())
            ->whereNotIn('id', function ($query) {
                $query->select('pointable_id')
                    ->from('loyalty_points')
                    ->where('type', 'expired')
                    ->whereNotNull('pointable_id');
            })
            ->get();

        $count = 0;

        foreach ($expiredPoints as $point) {
            DB::transaction(function () use ($point) {
                // Create expired record
                LoyaltyPoint::create([
                    'user_id' => $point->user_id,
                    'points' => -$point->points,
                    'type' => 'expired',
                    'description' => "Expired: {$point->description}",
                    'pointable_type' => LoyaltyPoint::class,
                    'pointable_id' => $point->id,
                ]);

                // Deduct from user balance
                $point->user->decrement('loyalty_points_balance', $point->points);
            });

            $count++;
        }

        return $count;
    }

    /**
     * Update user loyalty tier based on lifetime points
     */
    public function updateUserTier(User $user): void
    {
        $lifetimePoints = $user->loyalty_points_lifetime;

        $tier = match (true) {
            $lifetimePoints >= 10000 => 'diamond',
            $lifetimePoints >= 5000 => 'platinum',
            $lifetimePoints >= 2000 => 'gold',
            $lifetimePoints >= 500 => 'silver',
            default => 'bronze',
        };

        if ($user->loyalty_tier !== $tier) {
            $oldTier = $user->loyalty_tier;
            $user->update(['loyalty_tier' => $tier]);

            // Award bonus for tier upgrade
            if ($tier !== 'bronze') {
                $bonusPoints = match ($tier) {
                    'diamond' => 1000,
                    'platinum' => 500,
                    'gold' => 200,
                    'silver' => 50,
                    default => 0,
                };

                if ($bonusPoints > 0) {
                    $this->awardBonusPoints(
                        $user,
                        $bonusPoints,
                        "Tier upgrade bonus: {$oldTier} → {$tier}"
                    );
                }
            }
        }
    }

    /**
     * Get user's points summary
     */
    public function getUserPointsSummary(User $user): array
    {
        return [
            'balance' => $user->loyalty_points_balance,
            'lifetime' => $user->loyalty_points_lifetime,
            'tier' => $user->loyalty_tier,
            'earned_this_month' => LoyaltyPoint::where('user_id', $user->id)
                ->earned()
                ->whereBetween('created_at', [now()->startOfMonth(), now()])
                ->sum('points'),
            'redeemed_this_month' => abs(LoyaltyPoint::where('user_id', $user->id)
                ->redeemed()
                ->whereBetween('created_at', [now()->startOfMonth(), now()])
                ->sum('points')),
            'expiring_soon' => LoyaltyPoint::where('user_id', $user->id)
                ->earned()
                ->notExpired()
                ->where('expires_at', '<=', now()->addDays(30))
                ->sum('points'),
            'next_tier' => $this->getNextTier($user),
        ];
    }

    /**
     * Get next tier information
     */
    protected function getNextTier(User $user): ?array
    {
        $tiers = [
            'bronze' => ['name' => 'Silver', 'points_required' => 500],
            'silver' => ['name' => 'Gold', 'points_required' => 2000],
            'gold' => ['name' => 'Platinum', 'points_required' => 5000],
            'platinum' => ['name' => 'Diamond', 'points_required' => 10000],
        ];

        if (!isset($tiers[$user->loyalty_tier])) {
            return null;
        }

        $nextTier = $tiers[$user->loyalty_tier];
        $pointsNeeded = $nextTier['points_required'] - $user->loyalty_points_lifetime;

        return [
            'name' => $nextTier['name'],
            'points_required' => $nextTier['points_required'],
            'points_needed' => max(0, $pointsNeeded),
            'progress_percentage' => min(100, ($user->loyalty_points_lifetime / $nextTier['points_required']) * 100),
        ];
    }
}

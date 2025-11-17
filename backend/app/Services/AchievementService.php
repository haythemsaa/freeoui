<?php

namespace App\Services;

use App\Models\User;
use App\Models\Achievement;
use App\Models\UserAchievement;
use Illuminate\Support\Facades\DB;

class AchievementService
{
    /**
     * Check and unlock achievements for user
     */
    public function checkAchievements(User $user): array
    {
        $unlockedAchievements = [];

        $achievements = Achievement::active()->get();

        foreach ($achievements as $achievement) {
            $userAchievement = UserAchievement::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'achievement_id' => $achievement->id,
                ],
                [
                    'progress' => 0,
                ]
            );

            // Skip if already unlocked
            if ($userAchievement->isUnlocked()) {
                continue;
            }

            // Calculate progress
            $progress = $achievement->calculateProgress($user);
            $userAchievement->updateProgress($progress);

            // Check if achievement should be unlocked
            if ($achievement->checkCriteria($user) && !$userAchievement->isUnlocked()) {
                $this->unlockAchievement($user, $achievement);
                $unlockedAchievements[] = $achievement;
            }
        }

        return $unlockedAchievements;
    }

    /**
     * Unlock an achievement for user
     */
    public function unlockAchievement(User $user, Achievement $achievement): UserAchievement
    {
        return DB::transaction(function () use ($user, $achievement) {
            $userAchievement = UserAchievement::where('user_id', $user->id)
                ->where('achievement_id', $achievement->id)
                ->first();

            if (!$userAchievement) {
                $userAchievement = UserAchievement::create([
                    'user_id' => $user->id,
                    'achievement_id' => $achievement->id,
                    'progress' => 100,
                ]);
            }

            if (!$userAchievement->isUnlocked()) {
                $userAchievement->unlock();

                // Award points
                if ($achievement->points_reward > 0 && app()->has(LoyaltyService::class)) {
                    app(LoyaltyService::class)->awardBonusPoints(
                        $user,
                        $achievement->points_reward,
                        "Achievement unlocked: {$achievement->name}"
                    );
                }

                // TODO: Send notification
            }

            return $userAchievement;
        });
    }

    /**
     * Get user's achievement progress
     */
    public function getUserProgress(User $user): array
    {
        $achievements = Achievement::active()->get();
        $userAchievements = UserAchievement::where('user_id', $user->id)
            ->get()
            ->keyBy('achievement_id');

        $progress = [];

        foreach ($achievements as $achievement) {
            $userAchievement = $userAchievements->get($achievement->id);

            $progress[] = [
                'achievement' => [
                    'id' => $achievement->id,
                    'key' => $achievement->key,
                    'name' => $achievement->name,
                    'description' => $achievement->description,
                    'icon' => $achievement->icon,
                    'tier' => $achievement->tier,
                    'points_reward' => $achievement->points_reward,
                ],
                'progress' => $userAchievement?->progress ?? 0,
                'unlocked' => $userAchievement?->isUnlocked() ?? false,
                'unlocked_at' => $userAchievement?->unlocked_at,
            ];
        }

        return $progress;
    }

    /**
     * Get user's unlocked achievements
     */
    public function getUnlockedAchievements(User $user): array
    {
        return UserAchievement::where('user_id', $user->id)
            ->unlocked()
            ->with('achievement')
            ->orderByDesc('unlocked_at')
            ->get()
            ->map(function ($userAchievement) {
                return [
                    'achievement' => $userAchievement->achievement,
                    'unlocked_at' => $userAchievement->unlocked_at,
                ];
            })
            ->toArray();
    }

    /**
     * Get achievement statistics
     */
    public function getStats(): array
    {
        $totalAchievements = Achievement::active()->count();
        $totalUnlocks = UserAchievement::unlocked()->count();
        $totalUsers = User::count();

        $popularAchievements = Achievement::active()
            ->withCount(['userAchievements' => function ($query) {
                $query->unlocked();
            }])
            ->orderByDesc('user_achievements_count')
            ->limit(5)
            ->get();

        return [
            'total_achievements' => $totalAchievements,
            'total_unlocks' => $totalUnlocks,
            'average_unlocks_per_user' => $totalUsers > 0 ? round($totalUnlocks / $totalUsers, 2) : 0,
            'most_popular' => $popularAchievements->map(function ($achievement) {
                return [
                    'name' => $achievement->name,
                    'unlocks' => $achievement->user_achievements_count,
                ];
            }),
        ];
    }

    /**
     * Seed default achievements
     */
    public function seedDefaultAchievements(): void
    {
        $achievements = [
            [
                'key' => 'first_scan',
                'name' => 'First Steps',
                'description' => 'Scan your first QR code',
                'icon' => 'qrcode',
                'tier' => 'bronze',
                'points_reward' => 10,
                'criteria' => ['min_scans' => 1],
            ],
            [
                'key' => 'scan_10',
                'name' => 'Regular Visitor',
                'description' => 'Scan 10 QR codes',
                'icon' => 'star',
                'tier' => 'silver',
                'points_reward' => 50,
                'criteria' => ['min_scans' => 10],
            ],
            [
                'key' => 'scan_50',
                'name' => 'Enthusiast',
                'description' => 'Scan 50 QR codes',
                'icon' => 'fire',
                'tier' => 'gold',
                'points_reward' => 200,
                'criteria' => ['min_scans' => 50],
            ],
            [
                'key' => 'scan_100',
                'name' => 'Master Explorer',
                'description' => 'Scan 100 QR codes',
                'icon' => 'trophy',
                'tier' => 'platinum',
                'points_reward' => 500,
                'criteria' => ['min_scans' => 100],
            ],
            [
                'key' => 'first_review',
                'name' => 'Critic',
                'description' => 'Write your first review',
                'icon' => 'comment',
                'tier' => 'bronze',
                'points_reward' => 20,
                'criteria' => ['min_reviews' => 1],
            ],
            [
                'key' => 'review_10',
                'name' => 'Reviewer',
                'description' => 'Write 10 reviews',
                'icon' => 'comments',
                'tier' => 'silver',
                'points_reward' => 100,
                'criteria' => ['min_reviews' => 10],
            ],
            [
                'key' => 'first_referral',
                'name' => 'Friend Bringer',
                'description' => 'Refer your first friend',
                'icon' => 'users',
                'tier' => 'bronze',
                'points_reward' => 30,
                'criteria' => ['min_referrals' => 1],
            ],
            [
                'key' => 'referral_5',
                'name' => 'Influencer',
                'description' => 'Refer 5 friends',
                'icon' => 'user-friends',
                'tier' => 'gold',
                'points_reward' => 250,
                'criteria' => ['min_referrals' => 5],
            ],
            [
                'key' => 'silver_tier',
                'name' => 'Silver Member',
                'description' => 'Reach Silver loyalty tier',
                'icon' => 'medal',
                'tier' => 'silver',
                'points_reward' => 100,
                'criteria' => ['loyalty_tier' => 'silver'],
            ],
            [
                'key' => 'gold_tier',
                'name' => 'Gold Member',
                'description' => 'Reach Gold loyalty tier',
                'icon' => 'medal',
                'tier' => 'gold',
                'points_reward' => 300,
                'criteria' => ['loyalty_tier' => 'gold'],
            ],
            [
                'key' => 'platinum_tier',
                'name' => 'Platinum Member',
                'description' => 'Reach Platinum loyalty tier',
                'icon' => 'crown',
                'tier' => 'platinum',
                'points_reward' => 600,
                'criteria' => ['loyalty_tier' => 'platinum'],
            ],
        ];

        foreach ($achievements as $achievementData) {
            Achievement::updateOrCreate(
                ['key' => $achievementData['key']],
                $achievementData
            );
        }
    }
}

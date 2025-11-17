<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class LeaderboardService
{
    /**
     * Get global leaderboard
     */
    public function getGlobalLeaderboard(string $period = 'weekly', int $limit = 100): Collection
    {
        $cacheKey = "leaderboard:global:{$period}";
        
        return Cache::remember($cacheKey, 300, function () use ($period, $limit) {
            $startDate = $this->getStartDate($period);
            
            return User::when($startDate, function ($query) use ($startDate) {
                    return $query->where('created_at', '>=', $startDate);
                })
                ->orderByDesc('points_balance')
                ->orderByDesc('level')
                ->limit($limit)
                ->get(['id', 'first_name', 'last_name', 'avatar_url', 'points_balance', 'level', 'coins_balance']);
        });
    }

    /**
     * Get friends leaderboard
     */
    public function getFriendsLeaderboard(User $user): Collection
    {
        $friendIds = $user->friends()->pluck('id')->push($user->id);
        
        return User::whereIn('id', $friendIds)
            ->orderByDesc('points_balance')
            ->get(['id', 'first_name', 'last_name', 'avatar_url', 'points_balance', 'level']);
    }

    /**
     * Get governorate leaderboard
     */
    public function getGovernorateLeaderboard(int $governorateId, int $limit = 50): Collection
    {
        $cacheKey = "leaderboard:governorate:{$governorateId}";
        
        return Cache::remember($cacheKey, 300, function () use ($governorateId, $limit) {
            return User::where('governorate_id', $governorateId)
                ->orderByDesc('points_balance')
                ->limit($limit)
                ->get(['id', 'first_name', 'last_name', 'avatar_url', 'points_balance', 'level']);
        });
    }

    /**
     * Get category leaderboard
     */
    public function getCategoryLeaderboard(int $categoryId, int $limit = 50): Collection
    {
        // Users who interact most with a specific category
        return User::whereHas('favorites.category', function ($query) use ($categoryId) {
                $query->where('id', $categoryId);
            })
            ->withCount(['favorites' => function ($query) use ($categoryId) {
                $query->whereHas('category', function ($q) use ($categoryId) {
                    $q->where('id', $categoryId);
                });
            }])
            ->orderByDesc('favorites_count')
            ->limit($limit)
            ->get(['id', 'first_name', 'last_name', 'avatar_url', 'points_balance']);
    }

    /**
     * Get user rank in global leaderboard
     */
    public function getUserRank(User $user, string $period = 'weekly'): int
    {
        $startDate = $this->getStartDate($period);
        
        return User::when($startDate, function ($query) use ($startDate) {
                return $query->where('created_at', '>=', $startDate);
            })
            ->where('points_balance', '>', $user->points_balance)
            ->count() + 1;
    }

    /**
     * Award weekly top 3 users
     */
    public function awardWeeklyWinners(): void
    {
        $topUsers = $this->getGlobalLeaderboard('weekly', 3);
        
        $rewards = [
            1 => 1000, // 1st place
            2 => 500,  // 2nd place
            3 => 250,  // 3rd place
        ];

        foreach ($topUsers as $index => $user) {
            $rank = $index + 1;
            if (isset($rewards[$rank])) {
                $user->increment('points_balance', $rewards[$rank]);
                $user->increment('coins_balance', $rewards[$rank] / 10);
            }
        }
    }

    /**
     * Clear leaderboard caches
     */
    public function clearCaches(): void
    {
        Cache::tags(['leaderboards'])->flush();
    }

    /**
     * Get start date for period
     */
    private function getStartDate(string $period): ?Carbon
    {
        return match($period) {
            'weekly' => Carbon::now()->startOfWeek(),
            'monthly' => Carbon::now()->startOfMonth(),
            'all_time' => null,
            default => Carbon::now()->startOfWeek(),
        };
    }
}

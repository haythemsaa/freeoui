<?php

namespace App\Services;

use App\Models\User;
use App\Models\Advantage;
use App\Models\Merchant;
use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RecommendationService
{
    /**
     * Get personalized recommendations for user
     */
    public function getPersonalizedRecommendations(User $user, int $limit = 10): Collection
    {
        $cacheKey = "recommendations:user:{$user->id}";

        return Cache::remember($cacheKey, 1800, function () use ($user, $limit) {
            $recommendations = collect();

            // 1. Collaborative Filtering (users with similar behavior)
            $collaborativeRecs = $this->getCollaborativeRecommendations($user, 3);
            $recommendations = $recommendations->merge($collaborativeRecs);

            // 2. Content-Based (user's favorite categories)
            $contentRecs = $this->getContentBasedRecommendations($user, 3);
            $recommendations = $recommendations->merge($contentRecs);

            // 3. Location-Based (nearby trending)
            $locationRecs = $this->getLocationBasedRecommendations($user, 2);
            $recommendations = $recommendations->merge($locationRecs);

            // 4. Time-Based (ending soon, trending now)
            $timeRecs = $this->getTimeSensitiveRecommendations($user, 2);
            $recommendations = $recommendations->merge($timeRecs);

            // Remove duplicates and shuffle
            return $recommendations->unique('id')
                ->shuffle()
                ->take($limit)
                ->values();
        });
    }

    /**
     * Collaborative filtering recommendations
     */
    private function getCollaborativeRecommendations(User $user, int $limit): Collection
    {
        // Find users with similar favorites
        $userFavorites = $user->favorites()->pluck('advantage_id');

        if ($userFavorites->isEmpty()) {
            return collect();
        }

        $similarUsers = DB::table('user_favorites')
            ->whereIn('advantage_id', $userFavorites)
            ->where('user_id', '!=', $user->id)
            ->select('user_id', DB::raw('count(*) as common_favorites'))
            ->groupBy('user_id')
            ->orderByDesc('common_favorites')
            ->limit(10)
            ->pluck('user_id');

        if ($similarUsers->isEmpty()) {
            return collect();
        }

        // Get advantages favorited by similar users but not by current user
        return Advantage::whereHas('userFavorites', function ($query) use ($similarUsers) {
                $query->whereIn('user_id', $similarUsers);
            })
            ->whereDoesntHave('userFavorites', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where('is_active', true)
            ->where('ends_at', '>', now())
            ->with(['merchant', 'category'])
            ->inRandomOrder()
            ->limit($limit)
            ->get()
            ->map(function ($advantage) {
                $advantage->recommendation_reason = 'Aimé par des utilisateurs similaires';
                return $advantage;
            });
    }

    /**
     * Content-based recommendations
     */
    private function getContentBasedRecommendations(User $user, int $limit): Collection
    {
        // Get user's favorite categories
        $favoriteCategories = $user->favorites()
            ->with('advantage.category')
            ->get()
            ->pluck('advantage.category.id')
            ->filter()
            ->unique()
            ->take(5);

        if ($favoriteCategories->isEmpty()) {
            return collect();
        }

        return Advantage::whereHas('category', function ($query) use ($favoriteCategories) {
                $query->whereIn('id', $favoriteCategories);
            })
            ->whereDoesntHave('userFavorites', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where('is_active', true)
            ->where('ends_at', '>', now())
            ->with(['merchant', 'category'])
            ->inRandomOrder()
            ->limit($limit)
            ->get()
            ->map(function ($advantage) {
                $advantage->recommendation_reason = 'Basé sur vos préférences';
                return $advantage;
            });
    }

    /**
     * Location-based recommendations
     */
    private function getLocationBasedRecommendations(User $user, int $limit): Collection
    {
        $latestLocation = $user->latestLocation;

        if (!$latestLocation) {
            // Fallback to user's governorate
            if ($user->governorate_id) {
                return Advantage::whereHas('merchant', function ($query) use ($user) {
                        $query->where('governorate_id', $user->governorate_id);
                    })
                    ->where('is_active', true)
                    ->where('ends_at', '>', now())
                    ->whereDoesntHave('userFavorites', function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    })
                    ->with(['merchant', 'category'])
                    ->inRandomOrder()
                    ->limit($limit)
                    ->get()
                    ->map(function ($advantage) {
                        $advantage->recommendation_reason = 'Populaire dans votre région';
                        return $advantage;
                    });
            }
            return collect();
        }

        // Find nearby merchants within 5km
        $nearbyMerchants = Merchant::selectRaw(
            "*, ST_Distance_Sphere(
                point(longitude, latitude),
                point(?, ?)
            ) as distance",
            [$latestLocation->longitude, $latestLocation->latitude]
        )
            ->having('distance', '<', 5000)
            ->orderBy('distance')
            ->limit(20)
            ->pluck('id');

        return Advantage::whereIn('merchant_id', $nearbyMerchants)
            ->where('is_active', true)
            ->where('ends_at', '>', now())
            ->whereDoesntHave('userFavorites', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['merchant', 'category'])
            ->inRandomOrder()
            ->limit($limit)
            ->get()
            ->map(function ($advantage) {
                $advantage->recommendation_reason = 'À proximité de vous';
                return $advantage;
            });
    }

    /**
     * Time-sensitive recommendations
     */
    private function getTimeSensitiveRecommendations(User $user, int $limit): Collection
    {
        // Get advantages ending soon (within 3 days)
        return Advantage::where('is_active', true)
            ->where('ends_at', '>', now())
            ->where('ends_at', '<=', now()->addDays(3))
            ->whereDoesntHave('userFavorites', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['merchant', 'category'])
            ->orderBy('ends_at')
            ->limit($limit)
            ->get()
            ->map(function ($advantage) {
                $advantage->recommendation_reason = 'Se termine bientôt !';
                return $advantage;
            });
    }

    /**
     * Get trending advantages
     */
    public function getTrending(int $limit = 10): Collection
    {
        $cacheKey = "recommendations:trending:{$limit}";

        return Cache::remember($cacheKey, 600, function () use ($limit) {
            // Get most favorited in last 7 days
            return Advantage::withCount(['userFavorites' => function ($query) {
                    $query->where('created_at', '>=', now()->subDays(7));
                }])
                ->where('is_active', true)
                ->where('ends_at', '>', now())
                ->having('user_favorites_count', '>', 0)
                ->orderByDesc('user_favorites_count')
                ->with(['merchant', 'category'])
                ->limit($limit)
                ->get()
                ->map(function ($advantage) {
                    $advantage->recommendation_reason = 'Tendance cette semaine';
                    return $advantage;
                });
        });
    }

    /**
     * Get merchant recommendations
     */
    public function getMerchantRecommendations(User $user, int $limit = 5): Collection
    {
        $cacheKey = "recommendations:merchants:{$user->id}";

        return Cache::remember($cacheKey, 1800, function () use ($user, $limit) {
            // Find merchants in user's favorite categories
            $favoriteCategories = $user->favorites()
                ->with('advantage.merchant.category')
                ->get()
                ->pluck('advantage.merchant.category.id')
                ->filter()
                ->unique();

            if ($favoriteCategories->isEmpty()) {
                return Merchant::where('is_active', true)
                    ->inRandomOrder()
                    ->limit($limit)
                    ->get();
            }

            return Merchant::whereHas('category', function ($query) use ($favoriteCategories) {
                    $query->whereIn('id', $favoriteCategories);
                })
                ->where('is_active', true)
                ->withCount('advantages')
                ->having('advantages_count', '>', 0)
                ->inRandomOrder()
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get category recommendations based on user behavior
     */
    public function getCategoryRecommendations(User $user): Collection
    {
        // Analyze user's transaction and favorite patterns
        $transactionCategories = $user->transactions()
            ->with('merchant.category')
            ->get()
            ->pluck('merchant.category.id')
            ->filter()
            ->countBy()
            ->sortDesc();

        $favoriteCategories = $user->favorites()
            ->with('advantage.category')
            ->get()
            ->pluck('advantage.category.id')
            ->filter()
            ->countBy()
            ->sortDesc();

        // Merge and get top categories
        $topCategoryIds = $transactionCategories->merge($favoriteCategories)
            ->sortDesc()
            ->take(3)
            ->keys();

        return Category::whereIn('id', $topCategoryIds)
            ->with('children')
            ->get();
    }

    /**
     * Clear user recommendations cache
     */
    public function clearUserCache(User $user): void
    {
        Cache::forget("recommendations:user:{$user->id}");
        Cache::forget("recommendations:merchants:{$user->id}");
    }

    /**
     * Get smart bundle recommendations
     */
    public function getBundleRecommendations(User $user): Collection
    {
        // Find frequently purchased together advantages
        $userTransactions = $user->transactions()
            ->with('advantage')
            ->get()
            ->pluck('advantage.category_id')
            ->filter()
            ->unique();

        if ($userTransactions->count() < 2) {
            return collect();
        }

        // Find advantages from complementary categories
        return Advantage::whereIn('category_id', $userTransactions)
            ->where('is_active', true)
            ->where('ends_at', '>', now())
            ->whereDoesntHave('userFavorites', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['merchant', 'category'])
            ->limit(5)
            ->get()
            ->map(function ($advantage) {
                $advantage->recommendation_reason = 'Pack recommandé';
                return $advantage;
            });
    }
}

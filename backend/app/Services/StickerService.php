<?php

namespace App\Services;

use App\Models\User;
use App\Models\Category;
use App\Models\StickerCollection;
use App\Models\Merchant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class StickerService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Award sticker on merchant visit
     */
    public function awardStickerOnVisit(User $user, Merchant $merchant): ?StickerCollection
    {
        $category = $merchant->category;

        if (!$category) {
            return null;
        }

        // Get or create sticker for this category
        $sticker = StickerCollection::firstOrCreate(
            [
                'user_id' => $user->id,
                'category_id' => $category->id,
            ],
            [
                'sticker_type' => 'category',
                'sticker_tier' => 'bronze',
                'visit_count' => 0,
                'coin_multiplier' => 1.0,
                'unlocked_at' => now(),
            ]
        );

        $previousTier = $sticker->sticker_tier;
        $sticker->incrementVisit();

        // Check if tier upgraded
        if ($sticker->sticker_tier !== $previousTier) {
            $this->notificationService->send(
                $user,
                '⭐ Sticker amélioré !',
                "Votre sticker {$category->name} est maintenant {$sticker->sticker_tier}",
                'sticker',
                [
                    'category_id' => $category->id,
                    'tier' => $sticker->sticker_tier,
                    'multiplier' => $sticker->coin_multiplier,
                ],
                'high'
            );
        }

        return $sticker;
    }

    /**
     * Get user's sticker collection
     */
    public function getUserCollection(User $user): Collection
    {
        return StickerCollection::where('user_id', $user->id)
            ->with('category')
            ->orderByDesc('sticker_tier')
            ->orderByDesc('visit_count')
            ->get();
    }

    /**
     * Get collection completion percentage
     */
    public function getCompletionPercentage(User $user): float
    {
        $totalCategories = Category::count();
        $unlockedCategories = StickerCollection::where('user_id', $user->id)->count();

        if ($totalCategories === 0) {
            return 0;
        }

        return round(($unlockedCategories / $totalCategories) * 100, 2);
    }

    /**
     * Get sticker statistics
     */
    public function getStatistics(User $user): array
    {
        $stickers = $this->getUserCollection($user);

        return [
            'total_stickers' => $stickers->count(),
            'completion_percentage' => $this->getCompletionPercentage($user),
            'tier_breakdown' => [
                'bronze' => $stickers->where('sticker_tier', 'bronze')->count(),
                'silver' => $stickers->where('sticker_tier', 'silver')->count(),
                'gold' => $stickers->where('sticker_tier', 'gold')->count(),
                'diamond' => $stickers->where('sticker_tier', 'diamond')->count(),
            ],
            'total_visits' => $stickers->sum('visit_count'),
            'average_multiplier' => round($stickers->avg('coin_multiplier'), 2),
        ];
    }

    /**
     * Get global sticker leaderboard
     */
    public function getLeaderboard(int $limit = 50): Collection
    {
        $cacheKey = "sticker_leaderboard:{$limit}";

        return Cache::remember($cacheKey, 600, function () use ($limit) {
            return User::withCount('stickerCollection')
                ->having('sticker_collection_count', '>', 0)
                ->orderByDesc('sticker_collection_count')
                ->limit($limit)
                ->get(['id', 'first_name', 'last_name', 'avatar_url']);
        });
    }

    /**
     * Award special event sticker
     */
    public function awardSpecialSticker(User $user, string $eventType, array $metadata = []): StickerCollection
    {
        $sticker = StickerCollection::create([
            'user_id' => $user->id,
            'category_id' => null,
            'sticker_type' => 'special',
            'sticker_tier' => $metadata['tier'] ?? 'gold',
            'visit_count' => 1,
            'coin_multiplier' => $metadata['multiplier'] ?? 1.5,
            'unlocked_at' => now(),
        ]);

        $this->notificationService->send(
            $user,
            '🎁 Sticker spécial débloqué !',
            $metadata['message'] ?? "Vous avez reçu un sticker spécial {$eventType}",
            'sticker',
            ['sticker_id' => $sticker->id, 'event_type' => $eventType],
            'high'
        );

        return $sticker;
    }

    /**
     * Calculate total coin multiplier for user
     */
    public function getTotalMultiplier(User $user): float
    {
        $stickers = StickerCollection::where('user_id', $user->id)->get();

        if ($stickers->isEmpty()) {
            return 1.0;
        }

        // Average multiplier of all stickers
        return round($stickers->avg('coin_multiplier'), 2);
    }
}

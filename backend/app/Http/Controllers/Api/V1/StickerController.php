<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\StickerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StickerController extends Controller
{
    public function __construct(
        private StickerService $stickerService
    ) {}

    /**
     * Get user's sticker collection
     */
    public function collection(Request $request): JsonResponse
    {
        $collection = $this->stickerService->getUserCollection($request->user());
        $statistics = $this->stickerService->getStatistics($request->user());

        return response()->json([
            'success' => true,
            'collection' => $collection,
            'statistics' => $statistics,
        ]);
    }

    /**
     * Get sticker statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $statistics = $this->stickerService->getStatistics($request->user());
        $totalMultiplier = $this->stickerService->getTotalMultiplier($request->user());

        return response()->json([
            'success' => true,
            'statistics' => array_merge($statistics, [
                'total_multiplier' => $totalMultiplier,
            ]),
        ]);
    }

    /**
     * Get sticker leaderboard
     */
    public function leaderboard(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'limit' => 'nullable|integer|min:10|max:100',
        ]);

        $limit = $validated['limit'] ?? 50;

        $leaderboard = $this->stickerService->getLeaderboard($limit);

        return response()->json([
            'success' => true,
            'leaderboard' => $leaderboard->map(function ($user, $index) {
                return [
                    'rank' => $index + 1,
                    'user' => $user,
                    'sticker_count' => $user->sticker_collection_count,
                ];
            }),
        ]);
    }

    /**
     * Get sticker tiers and rewards
     */
    public function tiers(): JsonResponse
    {
        $tiers = [
            'bronze' => [
                'name' => 'Bronze',
                'visits_required' => 1,
                'coin_multiplier' => 1.0,
                'color' => '#CD7F32',
            ],
            'silver' => [
                'name' => 'Argent',
                'visits_required' => 5,
                'coin_multiplier' => 1.2,
                'color' => '#C0C0C0',
            ],
            'gold' => [
                'name' => 'Or',
                'visits_required' => 15,
                'coin_multiplier' => 1.5,
                'color' => '#FFD700',
            ],
            'diamond' => [
                'name' => 'Diamant',
                'visits_required' => 50,
                'coin_multiplier' => 2.0,
                'color' => '#B9F2FF',
            ],
        ];

        return response()->json([
            'success' => true,
            'tiers' => $tiers,
        ]);
    }

    /**
     * Showcase sticker (optional feature for user profile)
     */
    public function showcase(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sticker_ids' => 'required|array|max:3',
            'sticker_ids.*' => 'required|integer|exists:sticker_collections,id',
        ]);

        // Verify stickers belong to user
        $userStickers = $request->user()->stickerCollection()
            ->whereIn('id', $validated['sticker_ids'])
            ->get();

        if ($userStickers->count() !== count($validated['sticker_ids'])) {
            return response()->json([
                'success' => false,
                'message' => 'One or more stickers do not belong to you',
            ], 400);
        }

        // Update user's showcased stickers (could be stored in user metadata)
        $request->user()->update([
            'showcased_stickers' => $validated['sticker_ids'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Stickers showcased successfully',
            'showcased_stickers' => $userStickers,
        ]);
    }

    /**
     * Get category progress
     */
    public function categoryProgress(Request $request, int $categoryId): JsonResponse
    {
        $sticker = $request->user()->stickerCollection()
            ->where('category_id', $categoryId)
            ->first();

        if (!$sticker) {
            return response()->json([
                'success' => true,
                'sticker' => null,
                'progress' => [
                    'current_visits' => 0,
                    'current_tier' => null,
                    'next_tier' => 'bronze',
                    'visits_to_next_tier' => 1,
                ],
            ]);
        }

        // Calculate next tier
        $tierThresholds = [
            'bronze' => 1,
            'silver' => 5,
            'gold' => 15,
            'diamond' => 50,
        ];

        $nextTier = null;
        $visitsToNextTier = 0;

        foreach ($tierThresholds as $tier => $threshold) {
            if ($sticker->visit_count < $threshold) {
                $nextTier = $tier;
                $visitsToNextTier = $threshold - $sticker->visit_count;
                break;
            }
        }

        return response()->json([
            'success' => true,
            'sticker' => $sticker,
            'progress' => [
                'current_visits' => $sticker->visit_count,
                'current_tier' => $sticker->sticker_tier,
                'next_tier' => $nextTier,
                'visits_to_next_tier' => $visitsToNextTier,
            ],
        ]);
    }
}

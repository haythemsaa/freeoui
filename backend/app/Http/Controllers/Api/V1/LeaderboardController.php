<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LeaderboardController extends Controller
{
    public function __construct(
        private LeaderboardService $leaderboardService
    ) {}

    /**
     * Get global leaderboard
     */
    public function global(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => 'nullable|in:weekly,monthly,all_time',
            'limit' => 'nullable|integer|min:10|max:100',
        ]);

        $period = $validated['period'] ?? 'weekly';
        $limit = $validated['limit'] ?? 100;

        $leaderboard = $this->leaderboardService->getGlobalLeaderboard($period, $limit);
        $userRank = $this->leaderboardService->getUserRank($request->user(), $period);

        return response()->json([
            'success' => true,
            'period' => $period,
            'leaderboard' => $leaderboard->map(function ($user, $index) {
                return [
                    'rank' => $index + 1,
                    'user' => $user,
                ];
            }),
            'user_rank' => $userRank,
        ]);
    }

    /**
     * Get friends leaderboard
     */
    public function friends(Request $request): JsonResponse
    {
        $leaderboard = $this->leaderboardService->getFriendsLeaderboard($request->user());

        return response()->json([
            'success' => true,
            'leaderboard' => $leaderboard->map(function ($user, $index) {
                return [
                    'rank' => $index + 1,
                    'user' => $user,
                ];
            }),
        ]);
    }

    /**
     * Get governorate leaderboard
     */
    public function governorate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'governorate_id' => 'required|integer|exists:governorates,id',
            'limit' => 'nullable|integer|min:10|max:100',
        ]);

        $limit = $validated['limit'] ?? 50;

        $leaderboard = $this->leaderboardService->getGovernorateLeaderboard(
            $validated['governorate_id'],
            $limit
        );

        return response()->json([
            'success' => true,
            'governorate_id' => $validated['governorate_id'],
            'leaderboard' => $leaderboard->map(function ($user, $index) {
                return [
                    'rank' => $index + 1,
                    'user' => $user,
                ];
            }),
        ]);
    }

    /**
     * Get category leaderboard
     */
    public function category(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|integer|exists:categories,id',
            'limit' => 'nullable|integer|min:10|max:100',
        ]);

        $limit = $validated['limit'] ?? 50;

        $leaderboard = $this->leaderboardService->getCategoryLeaderboard(
            $validated['category_id'],
            $limit
        );

        return response()->json([
            'success' => true,
            'category_id' => $validated['category_id'],
            'leaderboard' => $leaderboard->map(function ($user, $index) {
                return [
                    'rank' => $index + 1,
                    'user' => $user,
                    'favorites_count' => $user->favorites_count ?? 0,
                ];
            }),
        ]);
    }

    /**
     * Get user's rank
     */
    public function rank(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => 'nullable|in:weekly,monthly,all_time',
        ]);

        $period = $validated['period'] ?? 'weekly';
        $rank = $this->leaderboardService->getUserRank($request->user(), $period);

        return response()->json([
            'success' => true,
            'period' => $period,
            'rank' => $rank,
            'points' => $request->user()->points_balance,
            'level' => $request->user()->level,
        ]);
    }
}

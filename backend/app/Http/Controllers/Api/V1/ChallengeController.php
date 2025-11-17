<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ChallengeService;
use App\Models\Challenge;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ChallengeController extends Controller
{
    public function __construct(
        private ChallengeService $challengeService
    ) {}

    /**
     * Get active challenges
     */
    public function index(Request $request): JsonResponse
    {
        $challenges = $this->challengeService->getActiveChallenges($request->user());

        return response()->json([
            'success' => true,
            'challenges' => $challenges->map(function ($item) {
                return [
                    'challenge' => $item['challenge'],
                    'participation' => $item['participation'],
                    'time_remaining' => $item['challenge']->ends_at->diffForHumans(),
                ];
            }),
        ]);
    }

    /**
     * Get specific challenge details
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $challenge = Challenge::findOrFail($id);
        $participation = $this->challengeService->getOrCreateParticipation($request->user(), $challenge);

        return response()->json([
            'success' => true,
            'challenge' => $challenge,
            'participation' => $participation,
            'leaderboard' => $this->challengeService->getChallengeLeaderboard($challenge, 10),
        ]);
    }

    /**
     * Join/participate in a challenge
     */
    public function participate(Request $request, int $id): JsonResponse
    {
        $challenge = Challenge::findOrFail($id);

        if (!$challenge->isOngoing()) {
            return response()->json([
                'success' => false,
                'message' => 'This challenge is not active',
            ], 400);
        }

        $participation = $this->challengeService->getOrCreateParticipation($request->user(), $challenge);

        return response()->json([
            'success' => true,
            'message' => 'Successfully joined challenge',
            'participation' => $participation,
        ]);
    }

    /**
     * Get user's challenge history
     */
    public function history(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'nullable|in:active,completed,expired',
        ]);

        $history = $this->challengeService->getUserHistory(
            $request->user(),
            $validated['status'] ?? null
        );

        return response()->json([
            'success' => true,
            'history' => $history,
        ]);
    }

    /**
     * Get challenge leaderboard
     */
    public function leaderboard(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'limit' => 'nullable|integer|min:10|max:100',
        ]);

        $challenge = Challenge::findOrFail($id);
        $limit = $validated['limit'] ?? 50;

        $leaderboard = $this->challengeService->getChallengeLeaderboard($challenge, $limit);

        return response()->json([
            'success' => true,
            'challenge' => $challenge,
            'leaderboard' => $leaderboard->map(function ($participation, $index) {
                return [
                    'rank' => $index + 1,
                    'user' => $participation->user,
                    'completed_at' => $participation->completed_at,
                    'progress_percentage' => $participation->progress_percentage,
                ];
            }),
        ]);
    }

    /**
     * Get user's progress in a specific challenge
     */
    public function progress(Request $request, int $id): JsonResponse
    {
        $challenge = Challenge::findOrFail($id);
        $participation = $this->challengeService->getOrCreateParticipation($request->user(), $challenge);

        return response()->json([
            'success' => true,
            'challenge' => $challenge,
            'participation' => $participation,
            'progress' => [
                'percentage' => $participation->progress_percentage,
                'current' => $participation->progress['current'] ?? 0,
                'target' => $participation->progress['target'] ?? 0,
                'status' => $participation->status,
            ],
        ]);
    }

    /**
     * Get challenge statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $user = $request->user();

        $totalChallenges = $user->challengeParticipations()->count();
        $completedChallenges = $user->challengeParticipations()
            ->where('status', 'completed')
            ->count();
        $activeChallenges = $user->challengeParticipations()
            ->where('status', 'active')
            ->count();
        $totalPointsEarned = $user->challengeParticipations()
            ->where('status', 'completed')
            ->join('challenges', 'challenge_participations.challenge_id', '=', 'challenges.id')
            ->sum('challenges.reward_points');

        return response()->json([
            'success' => true,
            'statistics' => [
                'total_challenges' => $totalChallenges,
                'completed_challenges' => $completedChallenges,
                'active_challenges' => $activeChallenges,
                'completion_rate' => $totalChallenges > 0 ? round(($completedChallenges / $totalChallenges) * 100, 2) : 0,
                'total_points_earned' => $totalPointsEarned,
            ],
        ]);
    }
}

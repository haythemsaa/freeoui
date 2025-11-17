<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SocialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SocialController extends Controller
{
    public function __construct(
        private SocialService $socialService
    ) {}

    /**
     * Share an advantage
     */
    public function share(Request $request): JsonResponse
    {
        $request->validate([
            'advantage_id' => 'required|exists:advantages,id',
            'platform' => 'required|in:facebook,twitter,instagram,whatsapp,linkedin,telegram',
            'referral_code' => 'nullable|string',
        ]);

        $user = $request->user();

        try {
            $share = $this->socialService->shareAdvantage(
                $user,
                $request->advantage_id,
                $request->platform,
                $request->input('referral_code')
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Partage enregistré',
                'data' => [
                    'share' => $share,
                    'points_earned' => 5, // Points for sharing
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Track share click
     */
    public function trackClick(Request $request, int $shareId): JsonResponse
    {
        $share = \App\Models\SocialShare::findOrFail($shareId);

        $this->socialService->trackClick($share);

        return response()->json([
            'status' => 'success',
            'message' => 'Click enregistré',
        ]);
    }

    /**
     * Track share conversion
     */
    public function trackConversion(Request $request, int $shareId): JsonResponse
    {
        $share = \App\Models\SocialShare::findOrFail($shareId);

        $this->socialService->trackConversion($share);

        return response()->json([
            'status' => 'success',
            'message' => 'Conversion enregistrée',
        ]);
    }

    /**
     * Get user's share history
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();

        $shares = $user->shares()
            ->with(['advantage'])
            ->latest()
            ->paginate(20);

        $stats = [
            'total_shares' => $user->shares()->count(),
            'total_clicks' => $user->shares()->sum('clicks'),
            'total_conversions' => $user->shares()->sum('conversions'),
            'viral_coefficient' => $user->shares()->avg('viral_coefficient'),
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'shares' => $shares->items(),
                'stats' => $stats,
                'pagination' => [
                    'current_page' => $shares->currentPage(),
                    'total_pages' => $shares->lastPage(),
                    'total_items' => $shares->total(),
                ],
            ],
        ]);
    }

    /**
     * Get referral stats
     */
    public function referralStats(Request $request): JsonResponse
    {
        $user = $request->user();

        // Assuming referral system tracks referred users
        $referralCount = \App\Models\User::where('referred_by', $user->id)->count();
        $referralRewards = $user->points_balance; // Simplified

        return response()->json([
            'status' => 'success',
            'data' => [
                'referral_code' => $user->referral_code ?? $user->id,
                'total_referrals' => $referralCount,
                'rewards_earned' => $referralRewards,
                'pending_rewards' => 0, // Could track pending
            ],
        ]);
    }
}

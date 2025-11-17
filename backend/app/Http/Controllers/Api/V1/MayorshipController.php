<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\MayorshipService;
use App\Models\Merchant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MayorshipController extends Controller
{
    public function __construct(
        private MayorshipService $mayorshipService
    ) {}

    /**
     * Check in at merchant
     */
    public function checkin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'merchant_id' => 'required|integer|exists:merchants,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $merchant = Merchant::findOrFail($validated['merchant_id']);

        // Optional: Verify user is close to merchant location
        // if (isset($validated['latitude']) && isset($validated['longitude'])) {
        //     $distance = $this->calculateDistance(
        //         $validated['latitude'],
        //         $validated['longitude'],
        //         $merchant->latitude,
        //         $merchant->longitude
        //     );
        //     if ($distance > 0.1) { // 100 meters
        //         return response()->json([
        //             'success' => false,
        //             'message' => 'You must be near the merchant to check in',
        //         ], 400);
        //     }
        // }

        $result = $this->mayorshipService->recordCheckin($request->user(), $merchant);

        return response()->json([
            'success' => true,
            'message' => $result['became_mayor'] ?? false ? 'Congratulations! You are now the mayor!' : 'Check-in successful',
            'data' => $result,
        ]);
    }

    /**
     * Get current mayor of a merchant
     */
    public function currentMayor(Request $request, int $merchantId): JsonResponse
    {
        $merchant = Merchant::findOrFail($merchantId);
        $mayor = $this->mayorshipService->getCurrentMayor($merchant);

        return response()->json([
            'success' => true,
            'mayor' => $mayor ? [
                'user' => $mayor->user,
                'checkin_count' => $mayor->checkin_count,
                'claimed_at' => $mayor->claimed_at,
                'last_checkin_at' => $mayor->last_checkin_at,
            ] : null,
        ]);
    }

    /**
     * Get challengers (top check-in users)
     */
    public function challengers(Request $request, int $merchantId): JsonResponse
    {
        $validated = $request->validate([
            'limit' => 'nullable|integer|min:3|max:20',
        ]);

        $merchant = Merchant::findOrFail($merchantId);
        $limit = $validated['limit'] ?? 5;

        $challengers = $this->mayorshipService->getChallengers($merchant, $limit);

        return response()->json([
            'success' => true,
            'challengers' => $challengers->map(function ($mayorship, $index) {
                return [
                    'rank' => $index + 1,
                    'user' => $mayorship->user,
                    'checkin_count' => $mayorship->checkin_count,
                    'is_mayor' => $mayorship->is_active,
                    'last_checkin_at' => $mayorship->last_checkin_at,
                ];
            }),
        ]);
    }

    /**
     * Get user's mayorships
     */
    public function myMayorships(Request $request): JsonResponse
    {
        $mayorships = $request->user()->mayorships()
            ->where('is_active', true)
            ->where('last_checkin_at', '>', now()->subDays(30))
            ->with('merchant')
            ->get();

        return response()->json([
            'success' => true,
            'mayorships' => $mayorships->map(function ($mayorship) {
                return [
                    'merchant' => $mayorship->merchant,
                    'checkin_count' => $mayorship->checkin_count,
                    'claimed_at' => $mayorship->claimed_at,
                    'last_checkin_at' => $mayorship->last_checkin_at,
                ];
            }),
            'total' => $mayorships->count(),
        ]);
    }

    /**
     * Get user's check-in history
     */
    public function checkinHistory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'limit' => 'nullable|integer|min:10|max:100',
        ]);

        $limit = $validated['limit'] ?? 50;

        $history = $request->user()->mayorships()
            ->with('merchant')
            ->orderByDesc('last_checkin_at')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'history' => $history,
            'checkin_streak' => $request->user()->checkin_streak,
            'last_checkin_date' => $request->user()->last_checkin_date,
        ]);
    }

    /**
     * Get user's check-in stats
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        $totalCheckins = $user->mayorships()->sum('checkin_count');
        $uniqueMerchants = $user->mayorships()->distinct('merchant_id')->count();
        $activeMayorships = $user->mayorships()
            ->where('is_active', true)
            ->where('last_checkin_at', '>', now()->subDays(30))
            ->count();

        return response()->json([
            'success' => true,
            'stats' => [
                'total_checkins' => $totalCheckins,
                'unique_merchants' => $uniqueMerchants,
                'active_mayorships' => $activeMayorships,
                'current_streak' => $user->checkin_streak,
                'last_checkin_date' => $user->last_checkin_date,
            ],
        ]);
    }
}

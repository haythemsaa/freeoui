<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Boost;
use App\Services\BoostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoostController extends Controller
{
    public function __construct(
        private BoostService $boostService
    ) {}

    /**
     * Get merchant's boost campaigns
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Assuming user has merchant relationship
        $merchant = $user->merchant;
        
        if (!$merchant) {
            return response()->json([
                'status' => 'error',
                'message' => 'Merchant not found',
            ], 404);
        }

        $boosts = $merchant->boosts()
            ->with(['advantage'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => [
                'boosts' => $boosts->items(),
                'pagination' => [
                    'current_page' => $boosts->currentPage(),
                    'total_pages' => $boosts->lastPage(),
                    'total_items' => $boosts->total(),
                ],
            ],
        ]);
    }

    /**
     * Get boost details
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $boost = Boost::with(['advantage', 'merchant'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'boost' => $boost,
                'performance' => [
                    'impressions' => $boost->impressions,
                    'clicks' => $boost->clicks,
                    'ctr' => $boost->click_through_rate,
                    'conversion_rate' => $boost->conversion_rate,
                    'spent' => $boost->spent_amount,
                    'remaining_budget' => $boost->budget - $boost->spent_amount,
                ],
            ],
        ]);
    }

    /**
     * Create a boost campaign
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'advantage_id' => 'required|exists:advantages,id',
            'boost_type' => 'required|in:proximity,category,general',
            'budget' => 'required|numeric|min:50',
            'cost_per_click' => 'required|numeric|min:0.1',
            'cost_per_view' => 'required|numeric|min:0.05',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'target_radius_km' => 'nullable|numeric|min:1|max:100',
            'target_governorates' => 'nullable|array',
        ]);

        try {
            $user = $request->user();
            $merchant = $user->merchant;

            if (!$merchant) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Merchant not found',
                ], 404);
            }

            $boost = $this->boostService->createBoost(
                $merchant,
                $request->advantage_id,
                $request->boost_type,
                $request->budget,
                $request->cost_per_click,
                $request->cost_per_view,
                $request->start_date,
                $request->end_date,
                $request->only(['target_radius_km', 'target_governorates', 'target_categories'])
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Boost créé avec succès',
                'data' => [
                    'boost' => $boost,
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
     * Pause a boost campaign
     */
    public function pause(Request $request, int $id): JsonResponse
    {
        $boost = Boost::findOrFail($id);

        $this->boostService->pauseBoost($boost);

        return response()->json([
            'status' => 'success',
            'message' => 'Boost mis en pause',
            'data' => [
                'boost' => $boost->fresh(),
            ],
        ]);
    }

    /**
     * Resume a boost campaign
     */
    public function resume(Request $request, int $id): JsonResponse
    {
        $boost = Boost::findOrFail($id);

        $this->boostService->resumeBoost($boost);

        return response()->json([
            'status' => 'success',
            'message' => 'Boost repris',
            'data' => [
                'boost' => $boost->fresh(),
            ],
        ]);
    }

    /**
     * Record boost impression
     */
    public function recordImpression(Request $request, int $id): JsonResponse
    {
        $boost = Boost::findOrFail($id);
        $user = $request->user();

        $this->boostService->recordImpression($boost, $user);

        return response()->json([
            'status' => 'success',
        ]);
    }

    /**
     * Record boost click
     */
    public function recordClick(Request $request, int $id): JsonResponse
    {
        $boost = Boost::findOrFail($id);
        $user = $request->user();

        $this->boostService->recordClick($boost, $user);

        return response()->json([
            'status' => 'success',
        ]);
    }
}

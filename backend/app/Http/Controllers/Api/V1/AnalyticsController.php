<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    public function __construct(
        private AnalyticsService $analyticsService
    ) {}

    /**
     * Get dashboard analytics (for merchants)
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $merchant = $user->merchant;

        if (!$merchant) {
            return response()->json([
                'status' => 'error',
                'message' => 'Merchant not found',
            ], 404);
        }

        $dateRange = $request->input('range', '7d'); // 7d, 30d, 90d
        $startDate = match($dateRange) {
            '7d' => Carbon::now()->subDays(7),
            '30d' => Carbon::now()->subDays(30),
            '90d' => Carbon::now()->subDays(90),
            default => Carbon::now()->subDays(7),
        };

        $metrics = [
            'total_views' => $merchant->advantages()->sum('views_count'),
            'total_scans' => $merchant->advantages()->sum('scans_count'),
            'total_revenue' => $merchant->payments()->where('status', 'completed')->sum('amount'),
            'pending_commission' => $merchant->commissions()->where('status', 'pending')->sum('amount'),
            'conversion_rate' => $this->analyticsService->getConversionRate($merchant, $startDate),
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'metrics' => $metrics,
                'date_range' => $dateRange,
            ],
        ]);
    }

    /**
     * Get user engagement analytics
     */
    public function userEngagement(Request $request): JsonResponse
    {
        $user = $request->user();

        $engagement = [
            'total_favorites' => $user->favorites()->count(),
            'total_scans' => $user->transactions()->count(),
            'total_savings' => $user->total_savings_tnd,
            'level' => $user->level,
            'points' => $user->points_balance,
            'streak_days' => $user->streak_days ?? 0,
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'engagement' => $engagement,
            ],
        ]);
    }

    /**
     * Track custom event
     */
    public function trackEvent(Request $request): JsonResponse
    {
        $request->validate([
            'event_name' => 'required|string',
            'event_category' => 'required|string',
            'properties' => 'nullable|array',
        ]);

        $user = $request->user();

        $this->analyticsService->trackEvent(
            $user,
            $request->event_name,
            $request->event_category,
            $request->input('properties', [])
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Événement enregistré',
        ]);
    }

    /**
     * Get popular advantages (trending)
     */
    public function trending(Request $request): JsonResponse
    {
        $governorateId = $request->input('governorate_id');
        $categoryId = $request->input('category_id');
        $limit = $request->input('limit', 10);

        $advantages = \App\Models\Advantage::query()
            ->with(['merchant', 'category'])
            ->where('status', 'active')
            ->when($governorateId, function ($query) use ($governorateId) {
                $query->whereHas('merchant', function ($q) use ($governorateId) {
                    $q->where('governorate_id', $governorateId);
                });
            })
            ->when($categoryId, function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->orderByDesc('views_count')
            ->orderByDesc('scans_count')
            ->limit($limit)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'trending' => $advantages,
            ],
        ]);
    }

    /**
     * Get retention cohort analysis (admin only)
     */
    public function cohortAnalysis(Request $request): JsonResponse
    {
        // This would typically require admin privileges
        $startDate = Carbon::parse($request->input('start_date', Carbon::now()->subMonths(3)));
        $weeks = $request->input('weeks', 12);

        $cohortData = $this->analyticsService->getRetentionCohort($startDate, $weeks);

        return response()->json([
            'status' => 'success',
            'data' => [
                'cohort_analysis' => $cohortData,
            ],
        ]);
    }
}

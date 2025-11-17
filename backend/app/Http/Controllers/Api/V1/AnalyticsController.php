<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Advantage;
use App\Models\QRCode;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function dashboard(Request $request): JsonResponse
    {
        $merchant = $request->user();

        $cacheKey = "analytics:dashboard:{$merchant->id}";

        $data = Cache::remember($cacheKey, 300, function () use ($merchant) {
            $now = Carbon::now();
            $today = $now->copy()->startOfDay();
            $yesterday = $now->copy()->subDay()->startOfDay();
            $lastWeek = $now->copy()->subWeek();
            $lastMonth = $now->copy()->subMonth();

            // Today's scans
            $todayScans = $this->getScansCount($merchant->id, $today, $now);

            // Yesterday's scans
            $yesterdayScans = $this->getScansCount(
                $merchant->id,
                $yesterday,
                $yesterday->copy()->endOfDay()
            );

            // This week scans
            $weekScans = $this->getScansCount($merchant->id, $lastWeek, $now);

            // This month scans
            $monthScans = $this->getScansCount($merchant->id, $lastMonth, $now);

            // Daily scans for last 7 days
            $dailyScans = $this->getDailyScans($merchant->id, 7);

            // Top advantages
            $topAdvantages = $this->getTopAdvantages($merchant->id, 5);

            // Hourly distribution
            $hourlyDistribution = $this->getHourlyDistribution($merchant->id);

            // Category breakdown
            $categoryBreakdown = $this->getCategoryBreakdown($merchant->id);

            return [
                'today_scans' => $todayScans,
                'yesterday_scans' => $yesterdayScans,
                'week_scans' => $weekScans,
                'month_scans' => $monthScans,
                'scans_change' => $yesterdayScans > 0
                    ? round((($todayScans - $yesterdayScans) / $yesterdayScans) * 100, 1)
                    : 0,
                'daily_scans' => $dailyScans,
                'top_advantages' => $topAdvantages,
                'hourly_distribution' => $hourlyDistribution,
                'category_breakdown' => $categoryBreakdown,
            ];
        });

        return ResponseHelper::success($data);
    }

    /**
     * Get real-time statistics
     */
    public function realtime(Request $request): JsonResponse
    {
        $merchant = $request->user();

        $now = Carbon::now();
        $today = $now->copy()->startOfDay();

        $data = [
            'today_scans' => $this->getScansCount($merchant->id, $today, $now),
            'active_users' => $this->getActiveUsersCount($merchant->id),
            'current_hour_scans' => $this->getCurrentHourScans($merchant->id),
            'timestamp' => $now->toIso8601String(),
        ];

        return ResponseHelper::success($data);
    }

    /**
     * Get detailed analytics report
     */
    public function report(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $merchant = $request->user();
        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();

        $data = [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'summary' => $this->getPeriodSummary($merchant->id, $startDate, $endDate),
            'daily_breakdown' => $this->getDailyBreakdown($merchant->id, $startDate, $endDate),
            'advantage_performance' => $this->getAdvantagePerformance($merchant->id, $startDate, $endDate),
            'customer_insights' => $this->getCustomerInsights($merchant->id, $startDate, $endDate),
        ];

        return ResponseHelper::success($data);
    }

    /**
     * Get conversion funnel
     */
    public function funnel(Request $request): JsonResponse
    {
        $merchant = $request->user();

        $days = $request->input('days', 30);
        $startDate = Carbon::now()->subDays($days)->startOfDay();
        $endDate = Carbon::now();

        $advantageIds = Advantage::where('merchant_id', $merchant->id)
            ->pluck('id');

        // QR codes generated
        $generated = QRCode::whereIn('advantage_id', $advantageIds)
            ->whereBetween('generated_at', [$startDate, $endDate])
            ->count();

        // QR codes used
        $used = QRCode::whereIn('advantage_id', $advantageIds)
            ->where('status', 'used')
            ->whereBetween('generated_at', [$startDate, $endDate])
            ->count();

        // QR codes expired
        $expired = QRCode::whereIn('advantage_id', $advantageIds)
            ->where('status', 'expired')
            ->whereBetween('generated_at', [$startDate, $endDate])
            ->count();

        $data = [
            'period_days' => $days,
            'funnel' => [
                [
                    'stage' => 'Generated',
                    'count' => $generated,
                    'percentage' => 100,
                ],
                [
                    'stage' => 'Used',
                    'count' => $used,
                    'percentage' => $generated > 0 ? round(($used / $generated) * 100, 1) : 0,
                ],
                [
                    'stage' => 'Expired',
                    'count' => $expired,
                    'percentage' => $generated > 0 ? round(($expired / $generated) * 100, 1) : 0,
                ],
            ],
            'conversion_rate' => $generated > 0 ? round(($used / $generated) * 100, 1) : 0,
        ];

        return ResponseHelper::success($data);
    }

    // Protected helper methods

    protected function getScansCount(int $merchantId, Carbon $start, Carbon $end): int
    {
        return QRCode::whereHas('advantage', fn($q) => $q->where('merchant_id', $merchantId))
            ->where('status', 'used')
            ->whereBetween('validated_at', [$start, $end])
            ->count();
    }

    protected function getDailyScans(int $merchantId, int $days): array
    {
        $startDate = Carbon::now()->subDays($days)->startOfDay();

        return QRCode::whereHas('advantage', fn($q) => $q->where('merchant_id', $merchantId))
            ->where('status', 'used')
            ->where('validated_at', '>=', $startDate)
            ->selectRaw('DATE(validated_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    protected function getTopAdvantages(int $merchantId, int $limit): array
    {
        return DB::table('qr_codes')
            ->join('advantages', 'qr_codes.advantage_id', '=', 'advantages.id')
            ->where('advantages.merchant_id', $merchantId)
            ->where('qr_codes.status', 'used')
            ->select('advantages.title', DB::raw('COUNT(*) as scans'))
            ->groupBy('advantages.id', 'advantages.title')
            ->orderByDesc('scans')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    protected function getHourlyDistribution(int $merchantId): array
    {
        return QRCode::whereHas('advantage', fn($q) => $q->where('merchant_id', $merchantId))
            ->where('status', 'used')
            ->selectRaw('EXTRACT(HOUR FROM validated_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->toArray();
    }

    protected function getCategoryBreakdown(int $merchantId): array
    {
        return DB::table('qr_codes')
            ->join('advantages', 'qr_codes.advantage_id', '=', 'advantages.id')
            ->join('categories', 'advantages.category_id', '=', 'categories.id')
            ->where('advantages.merchant_id', $merchantId)
            ->where('qr_codes.status', 'used')
            ->select('categories.name', DB::raw('COUNT(*) as count'))
            ->groupBy('categories.id', 'categories.name')
            ->get()
            ->toArray();
    }

    protected function getActiveUsersCount(int $merchantId): int
    {
        $last24Hours = Carbon::now()->subDay();

        return QRCode::whereHas('advantage', fn($q) => $q->where('merchant_id', $merchantId))
            ->where('generated_at', '>=', $last24Hours)
            ->distinct('user_id')
            ->count('user_id');
    }

    protected function getCurrentHourScans(int $merchantId): int
    {
        $currentHour = Carbon::now()->startOfHour();

        return QRCode::whereHas('advantage', fn($q) => $q->where('merchant_id', $merchantId))
            ->where('status', 'used')
            ->where('validated_at', '>=', $currentHour)
            ->count();
    }

    protected function getPeriodSummary(int $merchantId, Carbon $start, Carbon $end): array
    {
        $qrCodes = QRCode::whereHas('advantage', fn($q) => $q->where('merchant_id', $merchantId))
            ->where('status', 'used')
            ->whereBetween('validated_at', [$start, $end]);

        return [
            'total_scans' => $qrCodes->count(),
            'total_revenue' => $qrCodes->sum('discounted_amount'),
            'total_savings' => $qrCodes->selectRaw('SUM(original_amount - discounted_amount) as savings')
                ->value('savings') ?? 0,
            'unique_customers' => $qrCodes->distinct('user_id')->count('user_id'),
        ];
    }

    protected function getDailyBreakdown(int $merchantId, Carbon $start, Carbon $end): array
    {
        return QRCode::whereHas('advantage', fn($q) => $q->where('merchant_id', $merchantId))
            ->where('status', 'used')
            ->whereBetween('validated_at', [$start, $end])
            ->selectRaw('
                DATE(validated_at) as date,
                COUNT(*) as scans,
                SUM(discounted_amount) as revenue,
                SUM(original_amount - discounted_amount) as savings
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    protected function getAdvantagePerformance(int $merchantId, Carbon $start, Carbon $end): array
    {
        return DB::table('qr_codes')
            ->join('advantages', 'qr_codes.advantage_id', '=', 'advantages.id')
            ->where('advantages.merchant_id', $merchantId)
            ->where('qr_codes.status', 'used')
            ->whereBetween('qr_codes.validated_at', [$start, $end])
            ->select(
                'advantages.title',
                DB::raw('COUNT(*) as scans'),
                DB::raw('SUM(qr_codes.discounted_amount) as revenue'),
                DB::raw('COUNT(DISTINCT qr_codes.user_id) as unique_customers')
            )
            ->groupBy('advantages.id', 'advantages.title')
            ->orderByDesc('scans')
            ->get()
            ->toArray();
    }

    protected function getCustomerInsights(int $merchantId, Carbon $start, Carbon $end): array
    {
        $qrCodes = QRCode::whereHas('advantage', fn($q) => $q->where('merchant_id', $merchantId))
            ->where('status', 'used')
            ->whereBetween('validated_at', [$start, $end]);

        $uniqueCustomers = $qrCodes->distinct('user_id')->count('user_id');
        $totalScans = $qrCodes->count();

        return [
            'unique_customers' => $uniqueCustomers,
            'total_scans' => $totalScans,
            'average_scans_per_customer' => $uniqueCustomers > 0
                ? round($totalScans / $uniqueCustomers, 2)
                : 0,
            'returning_customers' => $this->getReturningCustomers($merchantId, $start, $end),
        ];
    }

    protected function getReturningCustomers(int $merchantId, Carbon $start, Carbon $end): int
    {
        return DB::table('qr_codes')
            ->join('advantages', 'qr_codes.advantage_id', '=', 'advantages.id')
            ->where('advantages.merchant_id', $merchantId)
            ->where('qr_codes.status', 'used')
            ->whereBetween('qr_codes.validated_at', [$start, $end])
            ->groupBy('qr_codes.user_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();
    }
}

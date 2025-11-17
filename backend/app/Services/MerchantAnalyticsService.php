<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\Transaction;
use App\Models\Advantage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MerchantAnalyticsService
{
    /**
     * Get comprehensive analytics for merchant
     */
    public function getAnalytics(Merchant $merchant, string $period = 'month'): array
    {
        $startDate = $this->getStartDate($period);

        return [
            'overview' => $this->getOverview($merchant, $startDate),
            'revenue' => $this->getRevenueMetrics($merchant, $startDate),
            'advantages' => $this->getAdvantageMetrics($merchant, $startDate),
            'customers' => $this->getCustomerMetrics($merchant, $startDate),
            'engagement' => $this->getEngagementMetrics($merchant, $startDate),
            'trends' => $this->getTrends($merchant, $startDate),
        ];
    }

    /**
     * Get overview metrics
     */
    private function getOverview(Merchant $merchant, Carbon $startDate): array
    {
        return [
            'total_transactions' => Transaction::where('merchant_id', $merchant->id)
                ->where('created_at', '>=', $startDate)
                ->count(),
            'total_revenue' => Transaction::where('merchant_id', $merchant->id)
                ->where('created_at', '>=', $startDate)
                ->sum('amount_paid'),
            'active_advantages' => Advantage::where('merchant_id', $merchant->id)
                ->where('is_active', true)
                ->where('ends_at', '>', now())
                ->count(),
            'unique_customers' => Transaction::where('merchant_id', $merchant->id)
                ->where('created_at', '>=', $startDate)
                ->distinct('user_id')
                ->count('user_id'),
        ];
    }

    /**
     * Get revenue metrics
     */
    private function getRevenueMetrics(Merchant $merchant, Carbon $startDate): array
    {
        $transactions = Transaction::where('merchant_id', $merchant->id)
            ->where('created_at', '>=', $startDate)
            ->get();

        $previousPeriodStart = $this->getPreviousPeriodStart($startDate);
        $previousTransactions = Transaction::where('merchant_id', $merchant->id)
            ->where('created_at', '>=', $previousPeriodStart)
            ->where('created_at', '<', $startDate)
            ->get();

        $currentRevenue = $transactions->sum('amount_paid');
        $previousRevenue = $previousTransactions->sum('amount_paid');
        $growth = $previousRevenue > 0 
            ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 
            : 0;

        return [
            'total_revenue' => $currentRevenue,
            'average_transaction' => $transactions->count() > 0 
                ? $currentRevenue / $transactions->count() 
                : 0,
            'growth_percentage' => round($growth, 2),
            'commission_paid' => $transactions->sum('commission_amount'),
            'net_revenue' => $currentRevenue - $transactions->sum('commission_amount'),
        ];
    }

    /**
     * Get advantage performance metrics
     */
    private function getAdvantageMetrics(Merchant $merchant, Carbon $startDate): array
    {
        $advantages = Advantage::where('merchant_id', $merchant->id)
            ->withCount(['userFavorites', 'transactions'])
            ->get();

        $topAdvantages = $advantages->sortByDesc('transactions_count')->take(5);

        return [
            'total_advantages' => $advantages->count(),
            'active_advantages' => $advantages->where('is_active', true)->count(),
            'total_favorites' => $advantages->sum('user_favorites_count'),
            'total_redemptions' => $advantages->sum('transactions_count'),
            'conversion_rate' => $advantages->sum('user_favorites_count') > 0
                ? ($advantages->sum('transactions_count') / $advantages->sum('user_favorites_count')) * 100
                : 0,
            'top_performing' => $topAdvantages->map(function ($adv) {
                return [
                    'id' => $adv->id,
                    'title' => $adv->title,
                    'redemptions' => $adv->transactions_count,
                    'favorites' => $adv->user_favorites_count,
                ];
            }),
        ];
    }

    /**
     * Get customer metrics
     */
    private function getCustomerMetrics(Merchant $merchant, Carbon $startDate): array
    {
        $customers = Transaction::where('merchant_id', $merchant->id)
            ->where('created_at', '>=', $startDate)
            ->select('user_id', DB::raw('count(*) as visit_count'))
            ->groupBy('user_id')
            ->get();

        return [
            'total_customers' => $customers->count(),
            'new_customers' => Transaction::where('merchant_id', $merchant->id)
                ->where('created_at', '>=', $startDate)
                ->whereIn('user_id', function ($query) use ($merchant, $startDate) {
                    $query->select('user_id')
                        ->from('transactions')
                        ->where('merchant_id', $merchant->id)
                        ->groupBy('user_id')
                        ->havingRaw('MIN(created_at) >= ?', [$startDate]);
                })
                ->distinct('user_id')
                ->count('user_id'),
            'returning_customers' => $customers->where('visit_count', '>', 1)->count(),
            'average_visits_per_customer' => round($customers->avg('visit_count'), 2),
            'retention_rate' => $customers->count() > 0
                ? ($customers->where('visit_count', '>', 1)->count() / $customers->count()) * 100
                : 0,
        ];
    }

    /**
     * Get engagement metrics
     */
    private function getEngagementMetrics(Merchant $merchant, Carbon $startDate): array
    {
        $mayorships = $merchant->mayorships()
            ->where('last_checkin_at', '>=', $startDate)
            ->count();

        $reviews = $merchant->reviews()
            ->where('created_at', '>=', $startDate)
            ->get();

        return [
            'total_checkins' => $mayorships,
            'total_reviews' => $reviews->count(),
            'average_rating' => round($reviews->avg('rating'), 2),
            'favorites' => $merchant->userFavorites()
                ->where('created_at', '>=', $startDate)
                ->count(),
            'stories_views' => $merchant->stories()
                ->where('created_at', '>=', $startDate)
                ->sum('views_count'),
        ];
    }

    /**
     * Get daily/weekly trends
     */
    private function getTrends(Merchant $merchant, Carbon $startDate): array
    {
        $transactions = Transaction::where('merchant_id', $merchant->id)
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount_paid) as revenue')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'daily_transactions' => $transactions->pluck('count', 'date'),
            'daily_revenue' => $transactions->pluck('revenue', 'date'),
            'peak_day' => $transactions->sortByDesc('count')->first(),
            'average_daily_revenue' => round($transactions->avg('revenue'), 2),
        ];
    }

    /**
     * Get start date based on period
     */
    private function getStartDate(string $period): Carbon
    {
        return match($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };
    }

    /**
     * Get previous period start date
     */
    private function getPreviousPeriodStart(Carbon $currentStart): Carbon
    {
        $diff = now()->diffInDays($currentStart);
        return $currentStart->copy()->subDays($diff);
    }

    /**
     * Get customer lifetime value
     */
    public function getCustomerLTV(Merchant $merchant): array
    {
        $customers = Transaction::where('merchant_id', $merchant->id)
            ->select('user_id', DB::raw('SUM(amount_paid) as total_spent'), DB::raw('COUNT(*) as visit_count'))
            ->groupBy('user_id')
            ->get();

        return [
            'average_ltv' => round($customers->avg('total_spent'), 2),
            'top_customers' => $customers->sortByDesc('total_spent')->take(10)->values(),
        ];
    }

    /**
     * Export analytics to CSV
     */
    public function exportToCSV(Merchant $merchant, string $period): string
    {
        $analytics = $this->getAnalytics($merchant, $period);
        
        // TODO: Implement CSV export
        // This would use ExportService to generate CSV file

        return '/exports/merchant-analytics-' . $merchant->id . '-' . time() . '.csv';
    }
}

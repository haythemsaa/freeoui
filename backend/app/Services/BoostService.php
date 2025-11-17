<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\Advantage;
use App\Models\Boost;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BoostService
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Create a boost campaign
     */
    public function createBoost(
        Merchant $merchant,
        Model $boostable, // Merchant or Advantage
        string $boostType,
        float $totalBudget,
        Carbon $startDate,
        Carbon $endDate,
        array $options = []
    ): Boost {
        return DB::transaction(function () use ($merchant, $boostable, $boostType, $totalBudget, $startDate, $endDate, $options) {
            $boost = Boost::create([
                'merchant_id' => $merchant->id,
                'boostable_type' => get_class($boostable),
                'boostable_id' => $boostable->id,
                'boost_type' => $boostType,
                'daily_budget' => $options['daily_budget'] ?? ($totalBudget / max(1, $startDate->diffInDays($endDate))),
                'total_budget' => $totalBudget,
                'spent_amount' => 0,
                'cost_per_view' => $options['cost_per_view'] ?? 0.100,
                'cost_per_click' => $options['cost_per_click'] ?? 0.500,
                'target_audience' => $options['target_audience'] ?? null,
                'target_locations' => $options['target_locations'] ?? null,
                'status' => 'pending',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'priority' => $options['priority'] ?? 0,
            ]);

            // Update boostable flags
            if ($boostable instanceof Merchant) {
                $boostable->update([
                    'is_boosted' => true,
                    'boost_priority' => $boost->priority,
                    'boosted_until' => $endDate,
                ]);
            } elseif ($boostable instanceof Advantage) {
                $boostable->update([
                    'is_boosted' => true,
                    'boost_priority' => $boost->priority,
                    'boosted_until' => $endDate,
                ]);
            }

            return $boost;
        });
    }

    /**
     * Activate/approve a boost
     */
    public function activateBoost(Boost $boost): Boost
    {
        return DB::transaction(function () use ($boost) {
            // Charge initial amount or setup payment
            // For now, we'll just activate it

            $boost->approve();

            return $boost;
        });
    }

    /**
     * Record impression for boost
     */
    public function recordImpression(Boost $boost, ?User $user = null): void
    {
        $boost->recordImpression($user);

        // Charge merchant
        if ($boost->cost_per_view > 0) {
            $boost->merchant->increment('total_ad_spend', $boost->cost_per_view);
        }
    }

    /**
     * Record click for boost
     */
    public function recordClick(Boost $boost, ?User $user = null): void
    {
        $boost->recordClick($user);

        if ($boost->cost_per_click > 0) {
            $boost->merchant->increment('total_ad_spend', $boost->cost_per_click);
        }
    }

    /**
     * Record conversion for boost
     */
    public function recordConversion(Boost $boost, ?User $user = null): void
    {
        $boost->recordConversion($user);
    }

    /**
     * Get active boosts for display
     */
    public function getActiveBoosts(
        string $boostType = null,
        array $location = null,
        int $limit = 10
    ): array {
        $query = Boost::active()->orderByDesc('priority');

        if ($boostType) {
            $query->where('boost_type', $boostType);
        }

        // TODO: Add location filtering based on target_locations

        return $query->limit($limit)->get()->toArray();
    }

    /**
     * Get boost performance metrics
     */
    public function getBoostMetrics(Boost $boost): array
    {
        $ctr = $boost->impressions > 0
            ? ($boost->clicks / $boost->impressions) * 100
            : 0;

        $cpc = $boost->clicks > 0
            ? $boost->spent_amount / $boost->clicks
            : 0;

        $cpm = $boost->impressions > 0
            ? ($boost->spent_amount / $boost->impressions) * 1000
            : 0;

        $roi = $boost->spent_amount > 0
            ? (($boost->conversions * 50) / $boost->spent_amount) * 100 // Assume avg value 50 TND per conversion
            : 0;

        return [
            'impressions' => $boost->impressions,
            'clicks' => $boost->clicks,
            'conversions' => $boost->conversions,
            'spent_amount' => $boost->spent_amount,
            'total_budget' => $boost->total_budget,
            'budget_remaining' => $boost->total_budget - $boost->spent_amount,
            'budget_used_percent' => $boost->total_budget > 0
                ? ($boost->spent_amount / $boost->total_budget) * 100
                : 0,
            'ctr' => round($ctr, 2), // Click-through rate
            'conversion_rate' => $boost->conversion_rate,
            'cpc' => round($cpc, 3), // Cost per click
            'cpm' => round($cpm, 3), // Cost per 1000 impressions
            'roi' => round($roi, 2), // Return on investment
        ];
    }

    /**
     * Get merchant boost summary
     */
    public function getMerchantSummary(Merchant $merchant): array
    {
        $boosts = Boost::where('merchant_id', $merchant->id)->get();

        return [
            'total_boosts' => $boosts->count(),
            'active_boosts' => $boosts->where('status', 'active')->count(),
            'total_spent' => $merchant->total_ad_spend,
            'total_impressions' => $boosts->sum('impressions'),
            'total_clicks' => $boosts->sum('clicks'),
            'total_conversions' => $boosts->sum('conversions'),
            'average_ctr' => $boosts->avg('conversion_rate'),
        ];
    }

    /**
     * Pause a boost
     */
    public function pauseBoost(Boost $boost): Boost
    {
        $boost->pause();
        return $boost;
    }

    /**
     * Resume a boost
     */
    public function resumeBoost(Boost $boost): Boost
    {
        $boost->resume();
        return $boost;
    }

    /**
     * Process expired boosts
     */
    public function processExpiredBoosts(): int
    {
        $expiredBoosts = Boost::where('status', 'active')
            ->where('end_date', '<', now())
            ->get();

        foreach ($expiredBoosts as $boost) {
            $boost->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Update boostable
            $boostable = $boost->boostable;
            if ($boostable) {
                $boostable->update([
                    'is_boosted' => false,
                    'boost_priority' => 0,
                    'boosted_until' => null,
                ]);
            }
        }

        return $expiredBoosts->count();
    }
}

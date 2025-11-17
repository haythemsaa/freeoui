<?php

namespace App\Services;

use App\Models\User;
use App\Models\Transaction;
use App\Models\Cashback;
use Illuminate\Support\Facades\DB;

class CashbackService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Calculate and award cashback for transaction
     */
    public function awardCashback(Transaction $transaction): ?Cashback
    {
        $user = $transaction->user;
        $merchant = $transaction->merchant;
        $amount = $transaction->amount_paid;

        // Calculate cashback percentage based on user tier and merchant
        $cashbackRate = $this->calculateCashbackRate($user, $merchant, $amount);

        if ($cashbackRate <= 0) {
            return null;
        }

        $cashbackAmount = round($amount * ($cashbackRate / 100), 2);

        // Create cashback record
        $cashback = Cashback::create([
            'user_id' => $user->id,
            'transaction_id' => $transaction->id,
            'merchant_id' => $merchant->id,
            'amount' => $cashbackAmount,
            'rate' => $cashbackRate,
            'status' => 'pending',
            'eligible_at' => now()->addDays(7), // 7 days cooling period
        ]);

        return $cashback;
    }

    /**
     * Calculate smart cashback rate
     */
    private function calculateCashbackRate(User $user, $merchant, float $amount): float
    {
        $baseRate = 2.0; // 2% base rate

        // Bonus for premium users
        if ($user->is_premium) {
            $baseRate += 1.0;
        }

        // Bonus for user tier/level
        if ($user->level >= 5) {
            $baseRate += 0.5;
        }

        // Bonus for frequent shoppers at this merchant
        $visitCount = Transaction::where('user_id', $user->id)
            ->where('merchant_id', $merchant->id)
            ->count();

        if ($visitCount >= 10) {
            $baseRate += 1.0;
        } elseif ($visitCount >= 5) {
            $baseRate += 0.5;
        }

        // Bonus for high transaction amounts
        if ($amount >= 100) {
            $baseRate += 0.5;
        }

        // Weekend bonus
        if (now()->isWeekend()) {
            $baseRate += 0.5;
        }

        return min($baseRate, 10.0); // Cap at 10%
    }

    /**
     * Process eligible cashbacks
     */
    public function processEligibleCashbacks(): int
    {
        $eligibleCashbacks = Cashback::where('status', 'pending')
            ->where('eligible_at', '<=', now())
            ->get();

        $processed = 0;

        foreach ($eligibleCashbacks as $cashback) {
            DB::beginTransaction();

            try {
                // Credit cashback to user's wallet
                $cashback->user->increment('wallet_balance', $cashback->amount);

                // Mark as processed
                $cashback->update([
                    'status' => 'processed',
                    'processed_at' => now(),
                ]);

                // Send notification
                $this->notificationService->send(
                    $cashback->user,
                    '💰 Cashback reçu !',
                    "Vous avez reçu {$cashback->amount} TND de cashback de {$cashback->merchant->name}",
                    'cashback',
                    ['cashback_id' => $cashback->id, 'amount' => $cashback->amount]
                );

                DB::commit();
                $processed++;
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error("Failed to process cashback {$cashback->id}: " . $e->getMessage());
            }
        }

        return $processed;
    }

    /**
     * Get user cashback statistics
     */
    public function getUserStats(User $user): array
    {
        $cashbacks = Cashback::where('user_id', $user->id)->get();

        return [
            'total_cashback_earned' => $cashbacks->where('status', 'processed')->sum('amount'),
            'pending_cashback' => $cashbacks->where('status', 'pending')->sum('amount'),
            'total_transactions' => $cashbacks->count(),
            'average_rate' => round($cashbacks->avg('rate'), 2),
            'this_month_cashback' => $cashbacks->where('created_at', '>=', now()->startOfMonth())->sum('amount'),
        ];
    }

    /**
     * Get cashback history for user
     */
    public function getHistory(User $user, int $limit = 50): \Illuminate\Support\Collection
    {
        return Cashback::where('user_id', $user->id)
            ->with(['merchant', 'transaction'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Cancel cashback (in case of refund)
     */
    public function cancelCashback(Cashback $cashback): void
    {
        if ($cashback->status === 'processed') {
            // Deduct from wallet if already processed
            $cashback->user->decrement('wallet_balance', $cashback->amount);
        }

        $cashback->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Get cashback leaderboard
     */
    public function getLeaderboard(int $limit = 50): \Illuminate\Support\Collection
    {
        return User::withSum(['cashbacks' => function ($query) {
                $query->where('status', 'processed');
            }], 'amount')
            ->having('cashbacks_sum_amount', '>', 0)
            ->orderByDesc('cashbacks_sum_amount')
            ->limit($limit)
            ->get();
    }
}

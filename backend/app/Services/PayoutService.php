<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\MerchantPayout;
use App\Models\Commission;
use Illuminate\Support\Facades\DB;

class PayoutService
{
    /**
     * Create payout request for merchant
     */
    public function createPayout(
        Merchant $merchant,
        float $amount,
        string $method = 'bank_transfer',
        array $bankDetails = []
    ): MerchantPayout {
        if ($amount > $merchant->available_balance) {
            throw new \Exception('Insufficient available balance');
        }

        if ($method === 'bank_transfer' && empty($bankDetails)) {
            $bankDetails = [
                'bank_name' => $merchant->bank_name,
                'account_number' => $merchant->bank_account_number,
                'account_holder' => $merchant->bank_account_holder,
                'iban' => $merchant->iban,
            ];
        }

        return DB::transaction(function () use ($merchant, $amount, $method, $bankDetails) {
            // Get unpaid commissions
            $commissions = Commission::where('merchant_id', $merchant->id)
                ->approved()
                ->sum('commission_amount');

            $payout = MerchantPayout::create([
                'merchant_id' => $merchant->id,
                'amount' => $amount,
                'commission_deducted' => 0, // Commissions already deducted
                'net_amount' => $amount,
                'method' => $method,
                'status' => 'pending',
                'bank_details' => $bankDetails,
            ]);

            // Deduct from available balance
            $merchant->decrement('available_balance', $amount);

            return $payout;
        });
    }

    /**
     * Process a payout
     */
    public function processPayout(MerchantPayout $payout): MerchantPayout
    {
        if (!$payout->scopePending) {
            throw new \Exception('Only pending payouts can be processed');
        }

        $payout->markAsProcessing();

        // Here you would integrate with actual bank transfer API
        // For now, mark as processing

        return $payout;
    }

    /**
     * Complete a payout
     */
    public function completePayout(MerchantPayout $payout, string $transactionReference): MerchantPayout
    {
        return DB::transaction(function () use ($payout, $transactionReference) {
            $payout->markAsCompleted($transactionReference);

            // Mark related commissions as paid
            Commission::where('merchant_id', $payout->merchant_id)
                ->approved()
                ->whereNotIn('status', ['paid'])
                ->update(['status' => 'paid', 'paid_at' => now()]);

            return $payout;
        });
    }

    /**
     * Auto-payout for merchants with enabled auto-payout
     */
    public function processAutoPayouts(): int
    {
        $merchants = Merchant::where('auto_payout_enabled', true)
            ->where('available_balance', '>=', DB::raw('auto_payout_threshold'))
            ->get();

        $count = 0;
        foreach ($merchants as $merchant) {
            try {
                $this->createPayout(
                    $merchant,
                    $merchant->available_balance,
                    'bank_transfer'
                );
                $count++;
            } catch (\Exception $e) {
                \Log::error('Auto payout failed', [
                    'merchant_id' => $merchant->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    /**
     * Get merchant payout summary
     */
    public function getMerchantSummary(Merchant $merchant): array
    {
        $totalPayouts = MerchantPayout::where('merchant_id', $merchant->id)->sum('net_amount');
        $pendingPayouts = MerchantPayout::where('merchant_id', $merchant->id)->pending()->sum('net_amount');
        $completedPayouts = MerchantPayout::where('merchant_id', $merchant->id)->completed()->sum('net_amount');

        return [
            'total_payouts' => $totalPayouts,
            'pending_payouts' => $pendingPayouts,
            'completed_payouts' => $completedPayouts,
            'available_balance' => $merchant->available_balance,
            'pending_balance' => $merchant->pending_balance,
            'auto_payout_enabled' => $merchant->auto_payout_enabled,
            'auto_payout_threshold' => $merchant->auto_payout_threshold,
        ];
    }
}

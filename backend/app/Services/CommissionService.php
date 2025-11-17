<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\Payment;
use App\Models\Booking;
use App\Models\Commission;
use Illuminate\Support\Facades\DB;

class CommissionService
{
    /**
     * Calculate commission for a payment
     */
    public function calculateCommission(Merchant $merchant, float $amount): array
    {
        $commissionRate = $merchant->commission_rate ?? 15.00;
        $commissionAmount = ($amount * $commissionRate) / 100;
        $merchantAmount = $amount - $commissionAmount;

        return [
            'transaction_amount' => $amount,
            'commission_rate' => $commissionRate,
            'commission_amount' => round($commissionAmount, 3),
            'merchant_amount' => round($merchantAmount, 3),
        ];
    }

    /**
     * Create commission record for payment
     */
    public function createForPayment(Payment $payment): Commission
    {
        $merchant = $payment->merchant;
        $commissionData = $this->calculateCommission($merchant, $payment->amount);

        return DB::transaction(function () use ($payment, $merchant, $commissionData) {
            $commission = Commission::create([
                'merchant_id' => $merchant->id,
                'payment_id' => $payment->id,
                'commission_type' => 'transaction',
                'transaction_amount' => $commissionData['transaction_amount'],
                'commission_rate' => $commissionData['commission_rate'],
                'commission_amount' => $commissionData['commission_amount'],
                'status' => 'pending',
            ]);

            // Update merchant pending balance
            $merchant->increment('pending_balance', $commissionData['merchant_amount']);

            return $commission;
        });
    }

    /**
     * Create commission for booking
     */
    public function createForBooking(Booking $booking, float $amount): Commission
    {
        $merchant = $booking->merchant;
        $commissionData = $this->calculateCommission($merchant, $amount);

        return DB::transaction(function () use ($booking, $merchant, $commissionData) {
            $commission = Commission::create([
                'merchant_id' => $merchant->id,
                'booking_id' => $booking->id,
                'commission_type' => 'booking',
                'transaction_amount' => $commissionData['transaction_amount'],
                'commission_rate' => $commissionData['commission_rate'],
                'commission_amount' => $commissionData['commission_amount'],
                'status' => 'pending',
            ]);

            $merchant->increment('pending_balance', $commissionData['merchant_amount']);

            return $commission;
        });
    }

    /**
     * Approve a commission
     */
    public function approve(Commission $commission): Commission
    {
        return DB::transaction(function () use ($commission) {
            $commission->approve();

            // Move from pending to available balance
            $merchant = $commission->merchant;
            $merchant->decrement('pending_balance', $commission->transaction_amount - $commission->commission_amount);
            $merchant->increment('available_balance', $commission->transaction_amount - $commission->commission_amount);

            return $commission;
        });
    }

    /**
     * Mark commission as paid
     */
    public function markAsPaid(Commission $commission): Commission
    {
        return DB::transaction(function () use ($commission) {
            $commission->markAsPaid();

            $merchant = $commission->merchant;
            $merchant->increment('lifetime_commissions_paid', $commission->commission_amount);

            return $commission;
        });
    }

    /**
     * Get merchant commission summary
     */
    public function getMerchantSummary(Merchant $merchant): array
    {
        $totalCommissions = Commission::where('merchant_id', $merchant->id)->sum('commission_amount');
        $pendingCommissions = Commission::where('merchant_id', $merchant->id)->pending()->sum('commission_amount');
        $approvedCommissions = Commission::where('merchant_id', $merchant->id)->approved()->sum('commission_amount');
        $paidCommissions = Commission::where('merchant_id', $merchant->id)->paid()->sum('commission_amount');

        return [
            'total_commissions' => $totalCommissions,
            'pending_commissions' => $pendingCommissions,
            'approved_commissions' => $approvedCommissions,
            'paid_commissions' => $paidCommissions,
            'commission_rate' => $merchant->commission_rate,
            'pending_balance' => $merchant->pending_balance,
            'available_balance' => $merchant->available_balance,
        ];
    }

    /**
     * Auto-approve old commissions (e.g., after 7 days)
     */
    public function autoApprove(int $daysOld = 7): int
    {
        $commissions = Commission::pending()
            ->where('created_at', '<=', now()->subDays($daysOld))
            ->get();

        $count = 0;
        foreach ($commissions as $commission) {
            $this->approve($commission);
            $count++;
        }

        return $count;
    }
}

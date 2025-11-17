<?php

namespace App\Services;

use App\Models\User;
use App\Models\Merchant;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\PaymentProviders\D17Provider;
use App\Services\PaymentProviders\FlouciProvider;
use App\Services\PaymentProviders\PaymeeProvider;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    protected $walletService;
    protected $commissionService;

    public function __construct(
        WalletService $walletService,
        CommissionService $commissionService
    ) {
        $this->walletService = $walletService;
        $this->commissionService = $commissionService;
    }

    /**
     * Create a payment
     */
    public function createPayment(
        User $user,
        float $amount,
        string $paymentType,
        string $paymentProvider,
        ?Merchant $merchant = null,
        ?Booking $booking = null,
        array $metadata = []
    ): Payment {
        $commissionData = ['merchant_amount' => $amount, 'commission' => 0];

        if ($merchant) {
            $commissionData = $this->commissionService->calculateCommission($merchant, $amount);
        }

        return Payment::create([
            'user_id' => $user->id,
            'merchant_id' => $merchant?->id,
            'booking_id' => $booking?->id,
            'payment_type' => $paymentType,
            'payment_provider' => $paymentProvider,
            'amount' => $amount,
            'fee' => 0, // Can be updated based on provider
            'merchant_amount' => $commissionData['merchant_amount'],
            'platform_commission' => $commissionData['commission_amount'] ?? 0,
            'currency' => 'TND',
            'status' => 'pending',
            'metadata' => $metadata,
        ]);
    }

    /**
     * Process payment with provider
     */
    public function processPayment(Payment $payment, array $providerData = []): array
    {
        try {
            $payment->markAsProcessing();

            $provider = $this->getProvider($payment->payment_provider);
            $result = $provider->processPayment($payment, $providerData);

            if ($result['success']) {
                return $this->handleSuccessfulPayment($payment, $result);
            } else {
                $payment->markAsFailed($result['message'] ?? 'Payment failed');
                return ['success' => false, 'message' => $result['message']];
            }
        } catch (\Exception $e) {
            $payment->markAsFailed($e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Process wallet payment
     */
    public function processWalletPayment(Payment $payment): array
    {
        try {
            $payment->markAsProcessing();

            if (!$this->walletService->hasBalance($payment->user, $payment->amount)) {
                $payment->markAsFailed('Insufficient wallet balance');
                return ['success' => false, 'message' => 'Insufficient wallet balance'];
            }

            return DB::transaction(function () use ($payment) {
                $this->walletService->debit(
                    $payment->user,
                    $payment->amount,
                    "Payment for {$payment->payment_type}",
                    $payment
                );

                return $this->handleSuccessfulPayment($payment, [
                    'success' => true,
                    'transaction_id' => 'WALLET-' . $payment->id,
                ]);
            });
        } catch (\Exception $e) {
            $payment->markAsFailed($e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Handle successful payment
     */
    protected function handleSuccessfulPayment(Payment $payment, array $result): array
    {
        return DB::transaction(function () use ($payment, $result) {
            $payment->markAsCompleted($result['transaction_id'] ?? null);
            $payment->update(['provider_response' => $result]);

            // Create commission if merchant payment
            if ($payment->merchant_id) {
                $this->commissionService->createForPayment($payment);
                $payment->merchant->increment('lifetime_earnings', $payment->merchant_amount);
            }

            // Award loyalty points
            if (app()->has(LoyaltyService::class)) {
                $points = (int) ($payment->amount / 10); // 1 point per 10 TND
                app(LoyaltyService::class)->awardPoints(
                    $payment->user,
                    $points,
                    'Payment reward',
                    $payment,
                    now()->addYear()
                );
            }

            return ['success' => true, 'payment' => $payment, 'result' => $result];
        });
    }

    /**
     * Refund a payment
     */
    public function refundPayment(Payment $payment, string $reason = null): array
    {
        if (!$payment->isCompleted()) {
            return ['success' => false, 'message' => 'Only completed payments can be refunded'];
        }

        try {
            return DB::transaction(function () use ($payment, $reason) {
                // If wallet payment, credit back to wallet
                if ($payment->payment_provider === 'wallet') {
                    $this->walletService->refund(
                        $payment->user,
                        $payment->amount,
                        "Refund for payment {$payment->payment_number}",
                        $payment
                    );
                } else {
                    // For other providers, implement provider-specific refund
                    $provider = $this->getProvider($payment->payment_provider);
                    $provider->refundPayment($payment);
                }

                $payment->markAsRefunded($reason);

                // Reverse merchant earnings
                if ($payment->merchant_id) {
                    $payment->merchant->decrement('lifetime_earnings', $payment->merchant_amount);
                    $payment->merchant->decrement('available_balance', $payment->merchant_amount);
                }

                return ['success' => true, 'payment' => $payment];
            });
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Top up wallet
     */
    public function topUpWallet(User $user, float $amount, string $provider, array $providerData = []): array
    {
        $payment = $this->createPayment($user, $amount, 'wallet_topup', $provider);

        $result = $this->processPayment($payment, $providerData);

        if ($result['success']) {
            $this->walletService->credit(
                $user,
                $amount,
                'Wallet top-up',
                $payment
            );
        }

        return $result;
    }

    /**
     * Get payment provider instance
     */
    protected function getProvider(string $provider): object
    {
        return match ($provider) {
            'd17' => new D17Provider(),
            'flouci' => new FlouciProvider(),
            'paymee' => new PaymeeProvider(),
            default => throw new \Exception("Unsupported payment provider: {$provider}"),
        };
    }

    /**
     * Get payment statistics for user
     */
    public function getUserPaymentStats(User $user): array
    {
        return [
            'total_payments' => Payment::where('user_id', $user->id)->completed()->count(),
            'total_spent' => Payment::where('user_id', $user->id)->completed()->sum('amount'),
            'average_payment' => Payment::where('user_id', $user->id)->completed()->avg('amount'),
            'last_payment' => Payment::where('user_id', $user->id)->completed()->latest()->first(),
        ];
    }
}

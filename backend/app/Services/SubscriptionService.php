<?php

namespace App\Services;

use App\Models\User;
use App\Models\Subscription;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function __construct(
        private PaymentService $paymentService,
        private NotificationService $notificationService
    ) {}

    /**
     * Subscribe user to FreeOui Plus
     */
    public function subscribe(User $user, string $planType = 'monthly', ?string $paymentMethod = 'wallet'): array
    {
        // Check if user already has active subscription
        if ($this->hasActiveSubscription($user)) {
            throw new \Exception('User already has an active subscription');
        }

        $pricing = $this->getPricing($planType);

        DB::beginTransaction();

        try {
            // Create payment
            $payment = $this->paymentService->createPayment($user, [
                'amount' => $pricing['amount'],
                'payment_method' => $paymentMethod,
                'payment_type' => 'subscription',
                'currency' => 'TND',
                'metadata' => [
                    'plan_type' => $planType,
                    'subscription' => true,
                ],
            ]);

            // Process payment
            $paymentResult = $this->paymentService->processPayment($payment);

            if ($paymentResult['status'] !== 'completed') {
                throw new \Exception('Payment failed');
            }

            // Create subscription
            $subscription = Subscription::create([
                'user_id' => $user->id,
                'plan_type' => $planType,
                'status' => 'active',
                'amount' => $pricing['amount'],
                'currency' => 'TND',
                'discount_percentage' => 15.0,
                'starts_at' => now(),
                'ends_at' => now()->add($pricing['duration']),
                'next_billing_date' => now()->add($pricing['duration']),
            ]);

            // Update user premium status
            $user->update(['is_premium' => true]);

            // Send confirmation notification
            $this->notificationService->send(
                $user,
                '✨ Bienvenue à FreeOui Plus !',
                "Votre abonnement {$planType} est maintenant actif. Profitez de -15% sur tous vos achats !",
                'subscription',
                ['subscription_id' => $subscription->id],
                'high'
            );

            DB::commit();

            return [
                'subscription' => $subscription,
                'payment' => $payment,
                'message' => 'Subscription activated successfully',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cancel subscription
     */
    public function cancel(Subscription $subscription, ?string $reason = null): Subscription
    {
        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        // Update user premium status
        $subscription->user->update(['is_premium' => false]);

        // Send notification
        $this->notificationService->send(
            $subscription->user,
            'Abonnement annulé',
            "Votre abonnement FreeOui Plus a été annulé. Merci de nous avoir fait confiance !",
            'subscription',
            ['subscription_id' => $subscription->id]
        );

        return $subscription;
    }

    /**
     * Renew subscription
     */
    public function renew(Subscription $subscription, ?string $paymentMethod = 'wallet'): array
    {
        $pricing = $this->getPricing($subscription->plan_type);

        DB::beginTransaction();

        try {
            // Create payment for renewal
            $payment = $this->paymentService->createPayment($subscription->user, [
                'amount' => $pricing['amount'],
                'payment_method' => $paymentMethod,
                'payment_type' => 'subscription_renewal',
                'currency' => 'TND',
                'metadata' => [
                    'subscription_id' => $subscription->id,
                    'plan_type' => $subscription->plan_type,
                ],
            ]);

            // Process payment
            $paymentResult = $this->paymentService->processPayment($payment);

            if ($paymentResult['status'] !== 'completed') {
                throw new \Exception('Renewal payment failed');
            }

            // Extend subscription
            $subscription->update([
                'status' => 'active',
                'ends_at' => $subscription->ends_at->add($pricing['duration']),
                'next_billing_date' => $subscription->ends_at,
                'renewed_at' => now(),
            ]);

            // Ensure user is premium
            $subscription->user->update(['is_premium' => true]);

            // Send notification
            $this->notificationService->send(
                $subscription->user,
                '🔄 Abonnement renouvelé !',
                "Votre abonnement FreeOui Plus a été renouvelé jusqu'au {$subscription->ends_at->format('d/m/Y')}",
                'subscription',
                ['subscription_id' => $subscription->id]
            );

            DB::commit();

            return [
                'subscription' => $subscription->fresh(),
                'payment' => $payment,
                'message' => 'Subscription renewed successfully',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Check if user has active subscription
     */
    public function hasActiveSubscription(User $user): bool
    {
        return Subscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->exists();
    }

    /**
     * Get user's active subscription
     */
    public function getActiveSubscription(User $user): ?Subscription
    {
        return Subscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->first();
    }

    /**
     * Get subscription pricing
     */
    public function getPricing(string $planType): array
    {
        return match($planType) {
            'monthly' => [
                'amount' => 9.90,
                'duration' => Carbon::DAYS_PER_WEEK * 4, // ~1 month
                'label' => 'Mensuel',
            ],
            'quarterly' => [
                'amount' => 24.90, // 17% discount
                'duration' => Carbon::DAYS_PER_WEEK * 13, // ~3 months
                'label' => 'Trimestriel',
            ],
            'yearly' => [
                'amount' => 89.90, // 25% discount
                'duration' => Carbon::DAYS_PER_YEAR,
                'label' => 'Annuel',
            ],
            default => throw new \Exception('Invalid plan type'),
        };
    }

    /**
     * Get all pricing plans
     */
    public function getAllPricing(): array
    {
        return [
            'monthly' => $this->getPricing('monthly'),
            'quarterly' => $this->getPricing('quarterly'),
            'yearly' => $this->getPricing('yearly'),
        ];
    }

    /**
     * Process automatic renewals (scheduled job)
     */
    public function processAutomaticRenewals(): int
    {
        $dueSubscriptions = Subscription::where('status', 'active')
            ->where('next_billing_date', '<=', now())
            ->get();

        $renewed = 0;

        foreach ($dueSubscriptions as $subscription) {
            try {
                $this->renew($subscription);
                $renewed++;
            } catch (\Exception $e) {
                // Failed renewal - notify user
                $this->notificationService->send(
                    $subscription->user,
                    '⚠️ Échec du renouvellement',
                    "Le renouvellement de votre abonnement a échoué. Veuillez vérifier votre mode de paiement.",
                    'subscription',
                    ['subscription_id' => $subscription->id],
                    'high'
                );

                // Mark as payment_failed
                $subscription->update(['status' => 'payment_failed']);
            }
        }

        return $renewed;
    }

    /**
     * Expire subscriptions
     */
    public function expireSubscriptions(): int
    {
        $expired = Subscription::where('status', 'active')
            ->where('ends_at', '<', now())
            ->get();

        foreach ($expired as $subscription) {
            $subscription->update(['status' => 'expired']);
            $subscription->user->update(['is_premium' => false]);

            // Notify user
            $this->notificationService->send(
                $subscription->user,
                'Abonnement expiré',
                "Votre abonnement FreeOui Plus a expiré. Renouvelez-le pour continuer à profiter de vos avantages !",
                'subscription',
                ['subscription_id' => $subscription->id]
            );
        }

        return $expired->count();
    }

    /**
     * Get subscription statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_active' => Subscription::where('status', 'active')->count(),
            'total_revenue_monthly' => Subscription::where('status', 'active')
                ->where('plan_type', 'monthly')
                ->sum('amount'),
            'total_revenue_all' => Subscription::whereIn('status', ['active', 'expired', 'cancelled'])
                ->sum('amount'),
            'churn_rate' => $this->calculateChurnRate(),
            'plans_breakdown' => [
                'monthly' => Subscription::where('status', 'active')->where('plan_type', 'monthly')->count(),
                'quarterly' => Subscription::where('status', 'active')->where('plan_type', 'quarterly')->count(),
                'yearly' => Subscription::where('status', 'active')->where('plan_type', 'yearly')->count(),
            ],
        ];
    }

    /**
     * Calculate churn rate
     */
    private function calculateChurnRate(): float
    {
        $activeLastMonth = Subscription::where('created_at', '>=', now()->subMonth())
            ->where('created_at', '<', now())
            ->count();

        $cancelledThisMonth = Subscription::where('cancelled_at', '>=', now()->startOfMonth())
            ->count();

        if ($activeLastMonth === 0) {
            return 0;
        }

        return round(($cancelledThisMonth / $activeLastMonth) * 100, 2);
    }
}

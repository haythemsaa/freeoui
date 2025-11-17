<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService
    ) {}

    /**
     * Get subscription plans and pricing
     */
    public function plans(): JsonResponse
    {
        $plans = $this->subscriptionService->getAllPricing();

        return response()->json([
            'success' => true,
            'plans' => $plans,
        ]);
    }

    /**
     * Get current subscription
     */
    public function current(Request $request): JsonResponse
    {
        $subscription = $this->subscriptionService->getActiveSubscription($request->user());

        return response()->json([
            'success' => true,
            'subscription' => $subscription,
            'is_premium' => $request->user()->is_premium,
        ]);
    }

    /**
     * Subscribe to FreeOui Plus
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_type' => 'required|in:monthly,quarterly,yearly',
            'payment_method' => 'required|in:wallet,d17,flouci,paymee',
        ]);

        try {
            $result = $this->subscriptionService->subscribe(
                $request->user(),
                $validated['plan_type'],
                $validated['payment_method']
            );

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'subscription' => $result['subscription'],
                'payment' => $result['payment'],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Cancel subscription
     */
    public function cancel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $subscription = $this->subscriptionService->getActiveSubscription($request->user());

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found',
            ], 404);
        }

        $cancelledSubscription = $this->subscriptionService->cancel(
            $subscription,
            $validated['reason'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Subscription cancelled successfully',
            'subscription' => $cancelledSubscription,
        ]);
    }

    /**
     * Renew subscription
     */
    public function renew(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:wallet,d17,flouci,paymee',
        ]);

        $subscription = $this->subscriptionService->getActiveSubscription($request->user());

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No subscription to renew',
            ], 404);
        }

        try {
            $result = $this->subscriptionService->renew(
                $subscription,
                $validated['payment_method']
            );

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'subscription' => $result['subscription'],
                'payment' => $result['payment'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get subscription history
     */
    public function history(Request $request): JsonResponse
    {
        $subscriptions = $request->user()->subscriptions()
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'subscriptions' => $subscriptions,
        ]);
    }
}

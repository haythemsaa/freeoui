<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    /**
     * Get user's payment history
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $payments = $user->payments()
            ->with(['merchant'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => [
                'payments' => $payments->items(),
                'pagination' => [
                    'current_page' => $payments->currentPage(),
                    'total_pages' => $payments->lastPage(),
                    'total_items' => $payments->total(),
                ],
            ],
        ]);
    }

    /**
     * Get payment details
     */
    public function show(Request $request, string $paymentNumber): JsonResponse
    {
        $user = $request->user();
        
        $payment = $user->payments()
            ->with(['merchant'])
            ->where('payment_number', $paymentNumber)
            ->firstOrFail();

        return response()->json([
            'status' => 'success',
            'data' => [
                'payment' => $payment,
            ],
        ]);
    }

    /**
     * Initiate a payment
     */
    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'payment_type' => 'required|in:booking_deposit,advantage_purchase,wallet_topup',
            'payment_provider' => 'required|in:d17,flouci,paymee,wallet',
            'amount' => 'required|numeric|min:1',
            'merchant_id' => 'required_if:payment_type,booking_deposit,advantage_purchase|exists:merchants,id',
            'advantage_id' => 'required_if:payment_type,advantage_purchase|exists:advantages,id',
        ]);

        try {
            $payment = $this->paymentService->initiatePayment(
                $request->user(),
                $request->payment_type,
                $request->amount,
                $request->payment_provider,
                $request->merchant_id,
                $request->only(['advantage_id', 'description', 'metadata'])
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Paiement initié',
                'data' => [
                    'payment' => [
                        'payment_number' => $payment->payment_number,
                        'amount' => $payment->amount,
                        'status' => $payment->status,
                        'payment_url' => $payment->payment_url,
                    ],
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de l\'initiation du paiement',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Payment webhook callback
     */
    public function webhook(Request $request, string $provider): JsonResponse
    {
        try {
            $result = $this->paymentService->handleWebhook($provider, $request->all());

            return response()->json([
                'status' => 'success',
                'message' => 'Webhook traité',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Verify payment status
     */
    public function verify(Request $request, string $paymentNumber): JsonResponse
    {
        $payment = Payment::where('payment_number', $paymentNumber)->firstOrFail();

        $result = $this->paymentService->verifyPayment($payment);

        return response()->json([
            'status' => 'success',
            'data' => [
                'payment' => [
                    'payment_number' => $payment->payment_number,
                    'status' => $payment->fresh()->status,
                    'verified' => $result['verified'],
                ],
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(
        private WalletService $walletService
    ) {}

    /**
     * Get user's wallet balance and transactions
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $wallet = $user->wallet;

        if (!$wallet) {
            $wallet = $this->walletService->createWallet($user);
        }

        $transactions = $wallet->transactions()
            ->latest()
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => [
                'wallet' => [
                    'balance' => $wallet->balance,
                    'currency' => $wallet->currency,
                    'status' => $wallet->status,
                ],
                'transactions' => $transactions->items(),
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'total_pages' => $transactions->lastPage(),
                    'total_items' => $transactions->total(),
                ],
            ],
        ]);
    }

    /**
     * Top up wallet
     */
    public function topUp(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:10|max:1000',
            'payment_provider' => 'required|in:d17,flouci,paymee',
        ]);

        try {
            $user = $request->user();
            $payment = $this->walletService->topUp(
                $user,
                $request->amount,
                $request->payment_provider
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Recharge initiée avec succès',
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
                'message' => 'Erreur lors de la recharge',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Withdraw from wallet
     */
    public function withdraw(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:20',
        ]);

        try {
            $user = $request->user();
            $this->walletService->withdraw(
                $user,
                $request->amount,
                'user_withdrawal',
                ['reason' => $request->input('reason', 'Retrait utilisateur')]
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Retrait effectué avec succès',
                'data' => [
                    'new_balance' => $user->wallet->fresh()->balance,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get transaction details
     */
    public function transaction(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $transaction = $user->wallet
            ->transactions()
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'transaction' => $transaction,
            ],
        ]);
    }
}

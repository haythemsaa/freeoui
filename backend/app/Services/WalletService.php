<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WalletService
{
    /**
     * Get or create wallet for user
     */
    public function getOrCreateWallet(User $user): Wallet
    {
        return Wallet::firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0,
                'pending_balance' => 0,
                'lifetime_earnings' => 0,
                'lifetime_spent' => 0,
                'currency' => 'TND',
                'is_active' => true,
            ]
        );
    }

    /**
     * Credit wallet
     */
    public function credit(
        User $user,
        float $amount,
        string $description,
        ?Model $reference = null
    ): WalletTransaction {
        $wallet = $this->getOrCreateWallet($user);

        return DB::transaction(function () use ($wallet, $amount, $description, $reference) {
            return $wallet->credit($amount, $description, $reference);
        });
    }

    /**
     * Debit wallet
     */
    public function debit(
        User $user,
        float $amount,
        string $description,
        ?Model $reference = null
    ): WalletTransaction {
        $wallet = $this->getOrCreateWallet($user);

        return DB::transaction(function () use ($wallet, $amount, $description, $reference) {
            return $wallet->debit($amount, $description, $reference);
        });
    }

    /**
     * Refund to wallet
     */
    public function refund(
        User $user,
        float $amount,
        string $description,
        ?Model $reference = null
    ): WalletTransaction {
        $wallet = $this->getOrCreateWallet($user);

        return DB::transaction(function () use ($wallet, $amount, $description, $reference) {
            return $wallet->refund($amount, $description, $reference);
        });
    }

    /**
     * Transfer between wallets
     */
    public function transfer(
        User $fromUser,
        User $toUser,
        float $amount,
        string $description
    ): array {
        return DB::transaction(function () use ($fromUser, $toUser, $amount, $description) {
            $debitTransaction = $this->debit($fromUser, $amount, "Transfer to {$toUser->name}: {$description}");
            $creditTransaction = $this->credit($toUser, $amount, "Transfer from {$fromUser->name}: {$description}");

            return [
                'debit' => $debitTransaction,
                'credit' => $creditTransaction,
            ];
        });
    }

    /**
     * Get wallet balance
     */
    public function getBalance(User $user): float
    {
        $wallet = $this->getOrCreateWallet($user);
        return (float) $wallet->balance;
    }

    /**
     * Check if user has sufficient balance
     */
    public function hasBalance(User $user, float $amount): bool
    {
        return $this->getBalance($user) >= $amount;
    }

    /**
     * Get wallet transaction history
     */
    public function getTransactionHistory(User $user, int $limit = 50): array
    {
        $wallet = $this->getOrCreateWallet($user);

        return WalletTransaction::where('wallet_id', $wallet->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get wallet summary
     */
    public function getSummary(User $user): array
    {
        $wallet = $this->getOrCreateWallet($user);

        return [
            'balance' => $wallet->balance,
            'pending_balance' => $wallet->pending_balance,
            'lifetime_earnings' => $wallet->lifetime_earnings,
            'lifetime_spent' => $wallet->lifetime_spent,
            'currency' => $wallet->currency,
            'is_active' => $wallet->is_active,
            'last_transaction_at' => $wallet->last_transaction_at,
        ];
    }
}

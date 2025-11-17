<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Wallet;

class WalletPolicy
{
    /**
     * Determine if the user can view the wallet.
     */
    public function view(User $user, Wallet $wallet): bool
    {
        return $user->id === $wallet->user_id;
    }

    /**
     * Determine if the user can top up the wallet.
     */
    public function topUp(User $user, Wallet $wallet): bool
    {
        return $user->id === $wallet->user_id && $wallet->status === 'active';
    }

    /**
     * Determine if the user can withdraw from the wallet.
     */
    public function withdraw(User $user, Wallet $wallet): bool
    {
        return $user->id === $wallet->user_id && $wallet->status === 'active';
    }
}

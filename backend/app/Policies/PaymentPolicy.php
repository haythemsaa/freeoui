<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    /**
     * Determine if the user can view the payment.
     */
    public function view(User $user, Payment $payment): bool
    {
        return $user->id === $payment->user_id;
    }

    /**
     * Determine if the user can create payments.
     */
    public function create(User $user): bool
    {
        return true; // All authenticated users can create payments
    }

    /**
     * Determine if the user can verify the payment.
     */
    public function verify(User $user, Payment $payment): bool
    {
        return $user->id === $payment->user_id;
    }
}

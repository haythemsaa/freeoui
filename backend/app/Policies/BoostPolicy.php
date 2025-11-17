<?php

namespace App\Policies;

use App\Models\Boost;
use App\Models\User;

class BoostPolicy
{
    /**
     * Determine if the user can view the boost.
     */
    public function view(User $user, Boost $boost): bool
    {
        return $user->merchant && $user->merchant->id === $boost->merchant_id;
    }

    /**
     * Determine if the user can create boosts.
     */
    public function create(User $user): bool
    {
        return $user->merchant !== null;
    }

    /**
     * Determine if the user can update the boost.
     */
    public function update(User $user, Boost $boost): bool
    {
        return $user->merchant && $user->merchant->id === $boost->merchant_id;
    }

    /**
     * Determine if the user can pause/resume the boost.
     */
    public function control(User $user, Boost $boost): bool
    {
        return $this->update($user, $boost);
    }
}

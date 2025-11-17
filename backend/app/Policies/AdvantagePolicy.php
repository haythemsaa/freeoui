<?php

namespace App\Policies;

use App\Models\Advantage;
use App\Models\Merchant;

class AdvantagePolicy
{
    /**
     * Determine if the merchant can view any advantages
     */
    public function viewAny(Merchant $merchant): bool
    {
        return $merchant->is_active;
    }

    /**
     * Determine if the merchant can view the advantage
     */
    public function view(Merchant $merchant, Advantage $advantage): bool
    {
        return $merchant->id === $advantage->merchant_id && $merchant->is_active;
    }

    /**
     * Determine if the merchant can create advantages
     */
    public function create(Merchant $merchant): bool
    {
        if (!$merchant->is_active) {
            return false;
        }

        // Check subscription limits
        $currentCount = $merchant->advantages()->count();
        $maxAdvantages = $this->getMaxAdvantagesForSubscription($merchant->subscription_plan);

        return $currentCount < $maxAdvantages;
    }

    /**
     * Determine if the merchant can update the advantage
     */
    public function update(Merchant $merchant, Advantage $advantage): bool
    {
        return $merchant->id === $advantage->merchant_id && $merchant->is_active;
    }

    /**
     * Determine if the merchant can delete the advantage
     */
    public function delete(Merchant $merchant, Advantage $advantage): bool
    {
        return $merchant->id === $advantage->merchant_id && $merchant->is_active;
    }

    /**
     * Determine if the merchant can activate/deactivate the advantage
     */
    public function toggleStatus(Merchant $merchant, Advantage $advantage): bool
    {
        return $merchant->id === $advantage->merchant_id && $merchant->is_active;
    }

    /**
     * Get maximum advantages based on subscription plan
     */
    protected function getMaxAdvantagesForSubscription(?string $plan): int
    {
        return match($plan) {
            'basic' => 5,
            'premium' => 20,
            'enterprise' => PHP_INT_MAX,
            default => 5,
        };
    }
}

<?php

namespace App\Policies;

use App\Models\Merchant;
use App\Models\QRCode;

class QRCodePolicy
{
    /**
     * Determine if the merchant can view any QR codes
     */
    public function viewAny(Merchant $merchant): bool
    {
        return $merchant->is_active;
    }

    /**
     * Determine if the merchant can view the QR code
     */
    public function view(Merchant $merchant, QRCode $qrCode): bool
    {
        return $merchant->id === $qrCode->advantage->merchant_id && $merchant->is_active;
    }

    /**
     * Determine if the merchant can validate the QR code
     */
    public function validate(Merchant $merchant, QRCode $qrCode): bool
    {
        // Merchant must own the advantage associated with the QR code
        if ($merchant->id !== $qrCode->advantage->merchant_id) {
            return false;
        }

        // Merchant must be active
        if (!$merchant->is_active) {
            return false;
        }

        // QR code must be active
        if ($qrCode->status !== 'active') {
            return false;
        }

        return true;
    }

    /**
     * Determine if the merchant can cancel the QR code
     */
    public function cancel(Merchant $merchant, QRCode $qrCode): bool
    {
        return $merchant->id === $qrCode->advantage->merchant_id
            && $merchant->is_active
            && $qrCode->status === 'active';
    }
}

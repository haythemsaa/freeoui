<?php

namespace App\Events;

use App\Models\Merchant;
use App\Models\QRCode;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QRCodeValidated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public QRCode $qrCode;
    public Merchant $merchant;

    /**
     * Create a new event instance.
     */
    public function __construct(QRCode $qrCode, Merchant $merchant)
    {
        $this->qrCode = $qrCode;
        $this->merchant = $merchant;
    }
}

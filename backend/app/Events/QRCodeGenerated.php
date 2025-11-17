<?php

namespace App\Events;

use App\Models\QRCode;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QRCodeGenerated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public QRCode $qrCode;
    public User $user;

    /**
     * Create a new event instance.
     */
    public function __construct(QRCode $qrCode, User $user)
    {
        $this->qrCode = $qrCode;
        $this->user = $user;
    }
}

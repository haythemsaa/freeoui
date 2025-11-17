<?php

namespace App\Events;

use App\Models\Advantage;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProximityAlertTriggered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public Advantage $advantage;
    public float $distance;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, Advantage $advantage, float $distance)
    {
        $this->user = $user;
        $this->advantage = $advantage;
        $this->distance = $distance;
    }
}

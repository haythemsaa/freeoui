<?php

namespace App\Listeners;

use App\Events\ProximityAlertTriggered;
use App\Services\NotificationService;

class SendProximityNotification
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function handle(ProximityAlertTriggered $event): void
    {
        $user = $event->user;
        $advantage = $event->advantage;
        $distance = $event->distance;

        // Check if user has proximity notifications enabled
        if (!$user->proximity_notifications_enabled) {
            return;
        }

        $this->notificationService->send(
            $user,
            '📍 Offre à proximité !',
            "{$advantage->title} - À " . round($distance, 1) . " km de vous",
            'proximity',
            [
                'advantage_id' => $advantage->id,
                'distance' => $distance,
            ],
            'normal',
            "/advantages/{$advantage->id}",
            $advantage->image_url
        );
    }
}

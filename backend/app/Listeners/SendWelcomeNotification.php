<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Jobs\SendEmailJob;
use App\Services\NotificationService;

class SendWelcomeNotification
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function handle(UserRegistered $event): void
    {
        $user = $event->user;

        // Send welcome push notification
        $this->notificationService->send(
            $user,
            'Bienvenue sur FreeOui ! 🎉',
            'Découvrez les meilleures offres près de chez vous.',
            'welcome',
            [],
            'normal'
        );

        // Send welcome email
        SendEmailJob::dispatch(
            $user,
            'Bienvenue sur FreeOui',
            'emails.welcome',
            ['user' => $user]
        );

        // Award welcome bonus points
        $user->increment('points_balance', 100);
    }
}

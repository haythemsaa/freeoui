<?php

namespace App\Providers;

use App\Events\MessageSent;
use App\Events\PaymentCompleted;
use App\Events\ProximityAlertTriggered;
use App\Events\UserRegistered;
use App\Listeners\NotifyNewMessage;
use App\Listeners\SendPaymentConfirmation;
use App\Listeners\SendProximityNotification;
use App\Listeners\SendWelcomeNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // Custom Events
        UserRegistered::class => [
            SendWelcomeNotification::class,
        ],

        PaymentCompleted::class => [
            SendPaymentConfirmation::class,
        ],

        ProximityAlertTriggered::class => [
            SendProximityNotification::class,
        ],

        MessageSent::class => [
            NotifyNewMessage::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}

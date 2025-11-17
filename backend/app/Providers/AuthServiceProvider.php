<?php

namespace App\Providers;

use App\Models\Boost;
use App\Models\Conversation;
use App\Models\Payment;
use App\Models\Wallet;
use App\Policies\BoostPolicy;
use App\Policies\ConversationPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\WalletPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Payment::class => PaymentPolicy::class,
        Wallet::class => WalletPolicy::class,
        Conversation::class => ConversationPolicy::class,
        Boost::class => BoostPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}

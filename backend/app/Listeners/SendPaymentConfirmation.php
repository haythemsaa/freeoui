<?php

namespace App\Listeners;

use App\Events\PaymentCompleted;
use App\Jobs\SendEmailJob;
use App\Services\NotificationService;

class SendPaymentConfirmation
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function handle(PaymentCompleted $event): void
    {
        $payment = $event->payment;
        $user = $payment->user;

        // Send push notification
        $this->notificationService->send(
            $user,
            'Paiement confirmé',
            "Votre paiement de {$payment->amount} TND a été confirmé avec succès.",
            'payment',
            ['payment_id' => $payment->id],
            'high'
        );

        // Send email confirmation
        SendEmailJob::dispatch(
            $user,
            'Confirmation de paiement - FreeOui',
            'emails.payment-confirmation',
            ['payment' => $payment]
        );
    }
}

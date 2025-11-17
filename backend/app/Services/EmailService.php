<?php

namespace App\Services;

use App\Models\Merchant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    /**
     * Send email
     */
    public function send(string $to, string $subject, string $view, array $data = []): bool
    {
        try {
            Log::info('Sending email', [
                'to' => $to,
                'subject' => $subject,
            ]);

            Mail::send($view, $data, function ($message) use ($to, $subject) {
                $message->to($to)
                    ->subject($subject)
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });

            Log::info('Email sent successfully', ['to' => $to]);
            return true;
        } catch (\Exception $e) {
            Log::error('Email sending failed', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send merchant welcome email
     */
    public function sendMerchantWelcome(Merchant $merchant): bool
    {
        return $this->send(
            $merchant->email,
            'Bienvenue sur FreeOui',
            'emails.merchant-welcome',
            ['merchant' => $merchant]
        );
    }

    /**
     * Send daily report to merchant
     */
    public function sendDailyReport(Merchant $merchant, array $stats): bool
    {
        return $this->send(
            $merchant->email,
            'Rapport quotidien FreeOui - ' . now()->format('d/m/Y'),
            'emails.daily-report',
            [
                'merchant' => $merchant,
                'stats' => $stats,
                'date' => now()->format('d/m/Y'),
            ]
        );
    }

    /**
     * Send password reset email
     */
    public function sendPasswordReset(string $email, string $token): bool
    {
        $resetUrl = config('app.frontend_url') . '/reset-password?token=' . $token;

        return $this->send(
            $email,
            'Réinitialisation de votre mot de passe',
            'emails.password-reset',
            [
                'resetUrl' => $resetUrl,
                'token' => $token,
            ]
        );
    }

    /**
     * Send low subscription alert
     */
    public function sendLowSubscriptionAlert(Merchant $merchant, int $daysRemaining): bool
    {
        return $this->send(
            $merchant->email,
            'Votre abonnement FreeOui arrive à expiration',
            'emails.low-subscription',
            [
                'merchant' => $merchant,
                'daysRemaining' => $daysRemaining,
            ]
        );
    }

    /**
     * Send new transaction notification
     */
    public function sendTransactionNotification(Merchant $merchant, array $transaction): bool
    {
        return $this->send(
            $merchant->email,
            'Nouvelle transaction FreeOui',
            'emails.new-transaction',
            [
                'merchant' => $merchant,
                'transaction' => $transaction,
            ]
        );
    }
}

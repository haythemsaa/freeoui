<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SMSService
{
    protected string $apiUrl;
    protected string $apiKey;
    protected string $senderId;

    public function __construct()
    {
        $this->apiUrl = config('services.sms.api_url', 'https://api.sms-provider.tn/send');
        $this->apiKey = config('services.sms.api_key', '');
        $this->senderId = config('services.sms.sender_id', 'FreeOui');
    }

    /**
     * Send SMS to a phone number
     */
    public function send(string $phoneNumber, string $message): bool
    {
        try {
            // Format phone number (ensure Tunisia country code)
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);

            Log::info('Sending SMS', [
                'phone' => $formattedPhone,
                'message_length' => strlen($message),
            ]);

            // In development/testing mode, just log the SMS
            if (config('app.env') !== 'production' || empty($this->apiKey)) {
                Log::info('SMS (dev mode)', [
                    'to' => $formattedPhone,
                    'message' => $message,
                ]);
                return true;
            }

            // Send actual SMS in production
            $response = Http::post($this->apiUrl, [
                'api_key' => $this->apiKey,
                'sender_id' => $this->senderId,
                'to' => $formattedPhone,
                'message' => $message,
            ]);

            if ($response->successful()) {
                Log::info('SMS sent successfully', ['phone' => $formattedPhone]);
                return true;
            }

            Log::error('SMS sending failed', [
                'phone' => $formattedPhone,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('SMS sending exception', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send OTP code
     */
    public function sendOTP(string $phoneNumber, string $code): bool
    {
        $message = "Votre code de vérification FreeOui est: {$code}. Ce code expire dans 10 minutes.";
        return $this->send($phoneNumber, $message);
    }

    /**
     * Send welcome message
     */
    public function sendWelcome(string $phoneNumber, string $name): bool
    {
        $message = "Bienvenue sur FreeOui, {$name}! Découvrez les meilleures offres près de chez vous.";
        return $this->send($phoneNumber, $message);
    }

    /**
     * Send proximity alert
     */
    public function sendProximityAlert(string $phoneNumber, string $advantageTitle, string $merchantName): bool
    {
        $message = "🔔 Offre à proximité! {$advantageTitle} chez {$merchantName}. Ouvrez l'app pour plus de détails.";
        return $this->send($phoneNumber, $message);
    }

    /**
     * Format phone number for Tunisia
     */
    protected function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove all non-numeric characters
        $cleaned = preg_replace('/[^0-9]/', '', $phoneNumber);

        // Add Tunisia country code if not present
        if (strlen($cleaned) === 8) {
            return '216' . $cleaned;
        }

        if (substr($cleaned, 0, 3) === '216') {
            return $cleaned;
        }

        return '216' . $cleaned;
    }
}

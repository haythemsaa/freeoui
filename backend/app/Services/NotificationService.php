<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send push notification via Firebase Cloud Messaging
     */
    public function sendPushNotification(
        string $fcmToken,
        string $title,
        string $body,
        array $data = []
    ): ?string {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . config('services.fcm.server_key'),
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'to' => $fcmToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                    'badge' => '1',
                ],
                'data' => $data,
                'priority' => 'high',
            ]);

            if ($response->successful()) {
                $result = $response->json();
                return $result['results'][0]['message_id'] ?? null;
            }

            Log::error('FCM notification failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Exception sending FCM notification', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Send SMS via Twilio
     */
    public function sendSMS(string $phoneNumber, string $message): bool
    {
        try {
            // Twilio implementation
            $accountSid = config('services.twilio.account_sid');
            $authToken = config('services.twilio.auth_token');
            $fromNumber = config('services.twilio.from_number');

            $response = Http::withBasicAuth($accountSid, $authToken)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                    'From' => $fromNumber,
                    'To' => $phoneNumber,
                    'Body' => $message,
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Failed to send SMS', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber,
            ]);

            return false;
        }
    }

    /**
     * Send OTP code via SMS
     */
    public function sendOTP(string $phoneNumber, string $code): bool
    {
        $message = "Votre code de vérification FreeOui est : {$code}. Ce code expire dans 5 minutes.";
        return $this->sendSMS($phoneNumber, $message);
    }
}

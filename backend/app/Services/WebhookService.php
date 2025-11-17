<?php

namespace App\Services;

use App\Models\Merchant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /**
     * Trigger a webhook
     */
    public function trigger(string $event, array $payload, ?Merchant $merchant = null): void
    {
        if (!$merchant || empty($merchant->webhook_url)) {
            return;
        }

        try {
            $webhookPayload = [
                'event' => $event,
                'timestamp' => now()->toIso8601String(),
                'data' => $payload,
            ];

            // Add signature for security
            $signature = $this->generateSignature($webhookPayload, $merchant->webhook_secret);

            $response = Http::timeout(10)
                ->withHeaders([
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event' => $event,
                ])
                ->post($merchant->webhook_url, $webhookPayload);

            if ($response->successful()) {
                Log::info('Webhook delivered successfully', [
                    'merchant_id' => $merchant->id,
                    'event' => $event,
                    'status' => $response->status(),
                ]);
            } else {
                Log::warning('Webhook delivery failed', [
                    'merchant_id' => $merchant->id,
                    'event' => $event,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Webhook exception', [
                'merchant_id' => $merchant->id,
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate HMAC signature for webhook payload
     */
    protected function generateSignature(array $payload, ?string $secret): string
    {
        if (empty($secret)) {
            $secret = config('app.key');
        }

        return hash_hmac('sha256', json_encode($payload), $secret);
    }

    /**
     * Verify webhook signature
     */
    public function verifySignature(array $payload, string $signature, string $secret): bool
    {
        $expectedSignature = $this->generateSignature($payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Available webhook events
     */
    public static function getAvailableEvents(): array
    {
        return [
            'qr_code.generated' => 'QR Code généré',
            'qr_code.validated' => 'QR Code validé',
            'qr_code.expired' => 'QR Code expiré',
            'advantage.created' => 'Avantage créé',
            'advantage.updated' => 'Avantage mis à jour',
            'advantage.deleted' => 'Avantage supprimé',
            'subscription.renewed' => 'Abonnement renouvelé',
            'subscription.expired' => 'Abonnement expiré',
        ];
    }
}

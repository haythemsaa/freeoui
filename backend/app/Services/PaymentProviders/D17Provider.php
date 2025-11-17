<?php

namespace App\Services\PaymentProviders;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class D17Provider
{
    protected string $apiUrl;
    protected string $apiKey;
    protected string $apiSecret;

    public function __construct()
    {
        $this->apiUrl = config('services.d17.api_url', 'https://api.d17.tn');
        $this->apiKey = config('services.d17.api_key');
        $this->apiSecret = config('services.d17.api_secret');
    }

    /**
     * Process payment via D17
     */
    public function processPayment(Payment $payment, array $data = []): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '/payments', [
                'amount' => $payment->amount * 1000, // Convert to millimes
                'currency' => 'TND',
                'reference' => $payment->payment_number,
                'description' => $payment->description,
                'callback_url' => route('webhooks.d17'),
                'customer' => [
                    'name' => $payment->user->name,
                    'email' => $payment->user->email,
                    'phone' => $payment->user->phone_number,
                ],
                'metadata' => $payment->metadata,
            ]);

            if ($response->successful()) {
                $result = $response->json();

                return [
                    'success' => true,
                    'transaction_id' => $result['transaction_id'] ?? $result['id'],
                    'payment_url' => $result['payment_url'] ?? null,
                    'status' => $result['status'] ?? 'pending',
                    'raw_response' => $result,
                ];
            }

            Log::error('D17 payment failed', [
                'payment_id' => $payment->id,
                'response' => $response->json(),
            ]);

            return [
                'success' => false,
                'message' => $response->json()['message'] ?? 'Payment failed',
                'raw_response' => $response->json(),
            ];
        } catch (\Exception $e) {
            Log::error('D17 payment exception', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify D17 webhook signature
     */
    public function verifyWebhook(array $payload, string $signature): bool
    {
        $computedSignature = hash_hmac('sha256', json_encode($payload), $this->apiSecret);
        return hash_equals($computedSignature, $signature);
    }

    /**
     * Handle D17 webhook callback
     */
    public function handleWebhook(array $payload): array
    {
        $paymentNumber = $payload['reference'] ?? null;
        $status = $payload['status'] ?? null;
        $transactionId = $payload['transaction_id'] ?? $payload['id'] ?? null;

        if (!$paymentNumber) {
            return ['success' => false, 'message' => 'Missing payment reference'];
        }

        $payment = Payment::where('payment_number', $paymentNumber)->first();

        if (!$payment) {
            return ['success' => false, 'message' => 'Payment not found'];
        }

        if ($status === 'completed' || $status === 'success') {
            $payment->markAsCompleted($transactionId);
            return ['success' => true, 'payment' => $payment];
        } elseif ($status === 'failed' || $status === 'error') {
            $payment->markAsFailed($payload['message'] ?? 'Payment failed');
            return ['success' => true, 'payment' => $payment];
        }

        return ['success' => true, 'message' => 'Webhook received'];
    }

    /**
     * Refund payment
     */
    public function refundPayment(Payment $payment): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '/refunds', [
                'transaction_id' => $payment->provider_transaction_id,
                'amount' => $payment->amount * 1000,
                'reason' => $payment->refund_reason ?? 'Customer refund',
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'refund_id' => $response->json()['id'] ?? null,
                ];
            }

            return [
                'success' => false,
                'message' => $response->json()['message'] ?? 'Refund failed',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}

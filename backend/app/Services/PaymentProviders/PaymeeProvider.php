<?php

namespace App\Services\PaymentProviders;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymeeProvider
{
    protected string $apiUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->apiUrl = config('services.paymee.api_url', 'https://api.paymee.tn');
        $this->apiKey = config('services.paymee.api_key');
    }

    /**
     * Process payment via Paymee
     */
    public function processPayment(Payment $payment, array $data = []): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Token ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '/api/v2/payments/create', [
                'vendor' => config('services.paymee.vendor_id'),
                'amount' => $payment->amount,
                'note' => $payment->description ?? 'FreeOui Payment',
                'first_name' => explode(' ', $payment->user->name)[0] ?? 'Customer',
                'last_name' => explode(' ', $payment->user->name)[1] ?? '',
                'email' => $payment->user->email,
                'phone' => $payment->user->phone_number,
                'return_url' => route('payment.return'),
                'cancel_url' => route('payment.cancel'),
                'webhook_url' => route('webhooks.paymee'),
                'order_id' => $payment->payment_number,
            ]);

            if ($response->successful()) {
                $result = $response->json();

                return [
                    'success' => true,
                    'transaction_id' => $result['data']['token'] ?? null,
                    'payment_url' => $result['data']['payment_url'] ?? null,
                    'status' => 'pending',
                    'raw_response' => $result,
                ];
            }

            Log::error('Paymee payment failed', [
                'payment_id' => $payment->id,
                'response' => $response->json(),
            ]);

            return [
                'success' => false,
                'message' => $response->json()['message'] ?? 'Payment creation failed',
                'raw_response' => $response->json(),
            ];
        } catch (\Exception $e) {
            Log::error('Paymee payment exception', [
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
     * Check payment status
     */
    public function checkPaymentStatus(string $token): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Token ' . $this->apiKey,
            ])->get($this->apiUrl . '/api/v2/payments/' . $token . '/check');

            if ($response->successful()) {
                $result = $response->json();

                return [
                    'success' => true,
                    'status' => $result['data']['payment_status'] ?? 'pending',
                    'data' => $result['data'] ?? [],
                ];
            }

            return [
                'success' => false,
                'message' => 'Status check failed',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle Paymee webhook
     */
    public function handleWebhook(array $payload): array
    {
        $token = $payload['token'] ?? null;
        $status = $payload['payment_status'] ?? null;
        $orderId = $payload['order_id'] ?? null;

        if (!$orderId) {
            return ['success' => false, 'message' => 'Missing order ID'];
        }

        $payment = Payment::where('payment_number', $orderId)->first();

        if (!$payment) {
            return ['success' => false, 'message' => 'Payment not found'];
        }

        if ($status === 'success' || $status === 'paid') {
            $payment->markAsCompleted($token);
            return ['success' => true, 'payment' => $payment];
        } elseif (in_array($status, ['failed', 'cancelled', 'expired'])) {
            $payment->markAsFailed("Payment {$status}");
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
                'Authorization' => 'Token ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '/api/v2/payments/' . $payment->provider_transaction_id . '/refund', [
                'amount' => $payment->amount,
                'reason' => $payment->refund_reason ?? 'Customer refund request',
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'refund_id' => $response->json()['data']['refund_id'] ?? null,
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

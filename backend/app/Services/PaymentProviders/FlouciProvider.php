<?php

namespace App\Services\PaymentProviders;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FlouciProvider
{
    protected string $apiUrl;
    protected string $appToken;
    protected string $appSecret;

    public function __construct()
    {
        $this->apiUrl = config('services.flouci.api_url', 'https://developers.flouci.com/api');
        $this->appToken = config('services.flouci.app_token');
        $this->appSecret = config('services.flouci.app_secret');
    }

    /**
     * Process payment via Flouci
     */
    public function processPayment(Payment $payment, array $data = []): array
    {
        try {
            // Generate payment
            $response = Http::withHeaders([
                'apppublic' => $this->appToken,
                'appsecret' => $this->appSecret,
            ])->post($this->apiUrl . '/generate_payment', [
                'app_token' => $this->appToken,
                'app_secret' => $this->appSecret,
                'amount' => $payment->amount * 1000, // Millimes
                'accept_card' => true,
                'session_timeout_secs' => 1200,
                'success_link' => route('payment.success'),
                'fail_link' => route('payment.failed'),
                'developer_tracking_id' => $payment->payment_number,
            ]);

            if ($response->successful()) {
                $result = $response->json()['result'] ?? $response->json();

                return [
                    'success' => true,
                    'transaction_id' => $result['payment_id'] ?? null,
                    'payment_url' => $result['link'] ?? null,
                    'status' => 'pending',
                    'raw_response' => $result,
                ];
            }

            Log::error('Flouci payment failed', [
                'payment_id' => $payment->id,
                'response' => $response->json(),
            ]);

            return [
                'success' => false,
                'message' => $response->json()['message'] ?? 'Payment generation failed',
                'raw_response' => $response->json(),
            ];
        } catch (\Exception $e) {
            Log::error('Flouci payment exception', [
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
     * Verify payment status
     */
    public function verifyPayment(string $paymentId): array
    {
        try {
            $response = Http::get($this->apiUrl . '/verify_payment/' . $paymentId);

            if ($response->successful()) {
                $result = $response->json()['result'] ?? $response->json();

                return [
                    'success' => true,
                    'status' => $result['status'] ?? 'pending',
                    'amount' => $result['amount'] ?? 0,
                    'data' => $result,
                ];
            }

            return [
                'success' => false,
                'message' => 'Verification failed',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle Flouci callback
     */
    public function handleCallback(array $data): array
    {
        $paymentId = $data['payment_id'] ?? null;

        if (!$paymentId) {
            return ['success' => false, 'message' => 'Missing payment ID'];
        }

        // Verify payment status
        $verification = $this->verifyPayment($paymentId);

        if (!$verification['success']) {
            return $verification;
        }

        $trackingId = $verification['data']['developer_tracking_id'] ?? null;

        if (!$trackingId) {
            return ['success' => false, 'message' => 'Missing tracking ID'];
        }

        $payment = Payment::where('payment_number', $trackingId)->first();

        if (!$payment) {
            return ['success' => false, 'message' => 'Payment not found'];
        }

        $status = $verification['data']['status'] ?? 'pending';

        if ($status === 'SUCCESS') {
            $payment->markAsCompleted($paymentId);
            return ['success' => true, 'payment' => $payment];
        } elseif (in_array($status, ['FAILED', 'EXPIRED', 'CANCELLED'])) {
            $payment->markAsFailed("Payment {$status}");
            return ['success' => true, 'payment' => $payment];
        }

        return ['success' => true, 'message' => 'Callback received'];
    }

    /**
     * Refund payment (Flouci may not support direct refunds)
     */
    public function refundPayment(Payment $payment): array
    {
        // Flouci may require manual refund processing
        // This is a placeholder - check Flouci docs for actual refund API

        Log::warning('Flouci refund requested - may require manual processing', [
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
        ]);

        return [
            'success' => false,
            'message' => 'Flouci refunds may require manual processing. Please contact Flouci support.',
        ];
    }
}

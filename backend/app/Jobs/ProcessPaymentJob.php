<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    public function __construct(
        public Payment $payment,
        public array $providerData = []
    ) {}

    public function handle(PaymentService $paymentService): void
    {
        try {
            Log::info('Processing payment', [
                'payment_number' => $this->payment->payment_number,
                'amount' => $this->payment->amount,
            ]);

            $result = $paymentService->processPayment($this->payment, $this->providerData);

            if ($result['success']) {
                Log::info('Payment processed successfully', [
                    'payment_number' => $this->payment->payment_number,
                ]);
            } else {
                Log::warning('Payment processing failed', [
                    'payment_number' => $this->payment->payment_number,
                    'reason' => $result['message'] ?? 'Unknown',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Payment processing exception', [
                'payment_number' => $this->payment->payment_number,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->payment->update([
            'status' => 'failed',
            'failure_reason' => $exception->getMessage(),
        ]);

        Log::error('Payment job failed', [
            'payment_number' => $this->payment->payment_number,
            'error' => $exception->getMessage(),
        ]);
    }
}

<?php

namespace App\Listeners;

use App\Events\QRCodeValidated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateMerchantStatistics implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(QRCodeValidated $event): void
    {
        try {
            $merchant = $event->merchant;
            $advantage = $event->qrCode->advantage;

            // Update advantage usage count
            DB::table('advantages')
                ->where('id', $advantage->id)
                ->increment('usage_count');

            // Clear cached statistics
            Cache::forget("merchant:{$merchant->id}:stats");
            Cache::forget("advantage:{$advantage->id}:stats");

            // Update daily statistics
            $date = now()->toDateString();
            $statsKey = "stats:merchant:{$merchant->id}:date:{$date}";

            $stats = Cache::get($statsKey, [
                'total_scans' => 0,
                'total_savings' => 0,
            ]);

            $stats['total_scans']++;

            // Calculate savings based on advantage type
            $savings = $this->calculateSavings($advantage, $event->qrCode);
            $stats['total_savings'] += $savings;

            Cache::put($statsKey, $stats, now()->addDays(30));

            Log::info('Merchant statistics updated', [
                'merchant_id' => $merchant->id,
                'advantage_id' => $advantage->id,
                'qr_code_id' => $event->qrCode->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update merchant statistics', [
                'error' => $e->getMessage(),
                'merchant_id' => $event->merchant->id,
            ]);
        }
    }

    /**
     * Calculate savings amount
     */
    protected function calculateSavings($advantage, $qrCode): float
    {
        return match($advantage->type) {
            'percentage' => $qrCode->original_amount * ($advantage->discount_percentage / 100),
            'fixed_amount' => $advantage->discount_amount,
            default => 0,
        };
    }
}

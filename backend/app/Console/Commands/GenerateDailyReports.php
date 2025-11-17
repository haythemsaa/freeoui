<?php

namespace App\Console\Commands;

use App\Models\Merchant;
use App\Models\QRCode;
use App\Services\EmailService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateDailyReports extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'reports:daily
                            {--date= : The date for the report (Y-m-d)}
                            {--merchant= : Generate report for specific merchant ID}';

    /**
     * The console command description.
     */
    protected $description = 'Generate and send daily reports to merchants';

    /**
     * Execute the console command.
     */
    public function handle(EmailService $emailService): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::yesterday();

        $merchantId = $this->option('merchant');

        $this->info("Generating daily reports for {$date->format('Y-m-d')}...");

        $merchantsQuery = Merchant::where('is_active', true)
            ->where('subscription_plan', '!=', null);

        if ($merchantId) {
            $merchantsQuery->where('id', $merchantId);
        }

        $merchants = $merchantsQuery->get();

        if ($merchants->isEmpty()) {
            $this->warn('No active merchants found.');
            return self::SUCCESS;
        }

        $this->info("Processing {$merchants->count()} merchants...");

        $bar = $this->output->createProgressBar($merchants->count());

        $successCount = 0;
        $failedCount = 0;

        foreach ($merchants as $merchant) {
            try {
                $stats = $this->generateMerchantStats($merchant, $date);

                if ($stats['total_scans'] === 0) {
                    // Skip merchants with no activity
                    $bar->advance();
                    continue;
                }

                // Send email report
                $emailService->sendDailyReport($merchant, $stats);

                $successCount++;
            } catch (\Exception $e) {
                $failedCount++;
                Log::error('Failed to generate daily report', [
                    'merchant_id' => $merchant->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Daily reports generated successfully!");
        $this->info("Sent: {$successCount} | Failed: {$failedCount}");

        return self::SUCCESS;
    }

    /**
     * Generate statistics for a merchant
     */
    protected function generateMerchantStats(Merchant $merchant, Carbon $date): array
    {
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        // Total scans
        $totalScans = QRCode::whereHas('advantage', function ($query) use ($merchant) {
            $query->where('merchant_id', $merchant->id);
        })
        ->where('status', 'used')
        ->whereBetween('validated_at', [$startOfDay, $endOfDay])
        ->count();

        // Total revenue (discounted amount)
        $totalRevenue = QRCode::whereHas('advantage', function ($query) use ($merchant) {
            $query->where('merchant_id', $merchant->id);
        })
        ->where('status', 'used')
        ->whereBetween('validated_at', [$startOfDay, $endOfDay])
        ->sum('discounted_amount');

        // Total savings given
        $totalSavings = QRCode::whereHas('advantage', function ($query) use ($merchant) {
            $query->where('merchant_id', $merchant->id);
        })
        ->where('status', 'used')
        ->whereBetween('validated_at', [$startOfDay, $endOfDay])
        ->selectRaw('SUM(original_amount - discounted_amount) as savings')
        ->value('savings') ?? 0;

        // Unique customers
        $uniqueCustomers = QRCode::whereHas('advantage', function ($query) use ($merchant) {
            $query->where('merchant_id', $merchant->id);
        })
        ->where('status', 'used')
        ->whereBetween('validated_at', [$startOfDay, $endOfDay])
        ->distinct('user_id')
        ->count('user_id');

        // Top advantages
        $topAdvantages = DB::table('qr_codes')
            ->join('advantages', 'qr_codes.advantage_id', '=', 'advantages.id')
            ->where('advantages.merchant_id', $merchant->id)
            ->where('qr_codes.status', 'used')
            ->whereBetween('qr_codes.validated_at', [$startOfDay, $endOfDay])
            ->select('advantages.title', DB::raw('COUNT(*) as count'))
            ->groupBy('advantages.id', 'advantages.title')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        return [
            'date' => $date->format('Y-m-d'),
            'total_scans' => $totalScans,
            'total_revenue' => $totalRevenue,
            'total_savings' => $totalSavings,
            'unique_customers' => $uniqueCustomers,
            'average_per_customer' => $uniqueCustomers > 0
                ? round($totalRevenue / $uniqueCustomers, 3)
                : 0,
            'top_advantages' => $topAdvantages,
        ];
    }
}

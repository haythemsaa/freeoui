<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\Commission;
use App\Models\MerchantPayout;
use Illuminate\Console\Command;
use Carbon\Carbon;

class GeneratePaymentReport extends Command
{
    protected $signature = 'payments:report {--period=month : Reporting period (day, week, month)}';

    protected $description = 'Generate payment and revenue report';

    public function handle(): int
    {
        $period = $this->option('period');

        $startDate = match ($period) {
            'day' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            default => now()->startOfMonth(),
        };

        $this->info("Generating report from {$startDate->format('Y-m-d')} to now...");
        $this->newLine();

        // Payments
        $totalPayments = Payment::where('created_at', '>=', $startDate)->completed()->count();
        $totalAmount = Payment::where('created_at', '>=', $startDate)->completed()->sum('amount');
        $walletPayments = Payment::where('created_at', '>=', $startDate)
            ->completed()
            ->where('payment_provider', 'wallet')
            ->count();

        $this->line("📊 <fg=cyan>Payments</>:");
        $this->line("   Total: {$totalPayments}");
        $this->line("   Amount: " . number_format($totalAmount, 3) . " TND");
        $this->line("   Wallet: {$walletPayments}");
        $this->newLine();

        // Commissions
        $totalCommissions = Commission::where('created_at', '>=', $startDate)->sum('commission_amount');
        $pendingCommissions = Commission::where('created_at', '>=', $startDate)->pending()->sum('commission_amount');
        $approvedCommissions = Commission::where('created_at', '>=', $startDate)->approved()->sum('commission_amount');
        $paidCommissions = Commission::where('created_at', '>=', $startDate)->paid()->sum('commission_amount');

        $this->line("💰 <fg=yellow>Commissions</>:");
        $this->line("   Total: " . number_format($totalCommissions, 3) . " TND");
        $this->line("   Pending: " . number_format($pendingCommissions, 3) . " TND");
        $this->line("   Approved: " . number_format($approvedCommissions, 3) . " TND");
        $this->line("   Paid: " . number_format($paidCommissions, 3) . " TND");
        $this->newLine();

        // Payouts
        $totalPayouts = MerchantPayout::where('created_at', '>=', $startDate)->count();
        $payoutAmount = MerchantPayout::where('created_at', '>=', $startDate)->sum('net_amount');
        $completedPayouts = MerchantPayout::where('created_at', '>=', $startDate)->completed()->count();

        $this->line("🏦 <fg=green>Payouts</>:");
        $this->line("   Total: {$totalPayouts}");
        $this->line("   Amount: " . number_format($payoutAmount, 3) . " TND");
        $this->line("   Completed: {$completedPayouts}");
        $this->newLine();

        // Platform revenue
        $platformRevenue = $totalCommissions;
        $platformProfit = $platformRevenue - $paidCommissions;

        $this->line("🚀 <fg=magenta>Platform Revenue</>:");
        $this->line("   Gross: " . number_format($platformRevenue, 3) . " TND");
        $this->line("   Net Profit: " . number_format($platformProfit, 3) . " TND");

        return Command::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Services\AnalyticsService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class GenerateAnalyticsReport extends Command
{
    protected $signature = 'analytics:report {--period=week : Period (day, week, month)}';

    protected $description = 'Generate analytics report';

    public function handle(AnalyticsService $analyticsService): int
    {
        $period = $this->option('period');

        [$startDate, $endDate] = match ($period) {
            'day' => [now()->startOfDay(), now()],
            'week' => [now()->startOfWeek(), now()],
            'month' => [now()->startOfMonth(), now()],
            default => [now()->startOfWeek(), now()],
        };

        $this->info("Generating analytics report from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}...");
        $this->newLine();

        $metrics = $analyticsService->getPlatformMetrics($startDate, $endDate);

        $this->line("📊 <fg=cyan>User Metrics</>:");
        $this->line("   New Users: {$metrics['new_users']}");
        $this->line("   DAU: {$metrics['dau']}");
        $this->line("   WAU: {$metrics['wau']}");
        $this->line("   MAU: {$metrics['mau']}");
        $this->line("   DAU/MAU Ratio: " . round($metrics['dau_mau_ratio'], 2) . "%");
        $this->newLine();

        $this->line("📱 <fg=yellow>Engagement Metrics</>:");
        $this->line("   Total Sessions: {$metrics['total_sessions']}");
        $this->line("   Total Events: {$metrics['total_events']}");
        $this->line("   Avg Sessions/User: " . round($metrics['avg_sessions_per_user'], 2));
        $this->newLine();

        $this->line("🖥️  <fg=green>Top Platforms</>:");
        foreach ($metrics['top_platforms'] as $platform) {
            $this->line("   {$platform['platform']}: {$platform['sessions']} sessions");
        }
        $this->newLine();

        $this->line("🎯 <fg=magenta>Top Events</>:");
        foreach (array_slice($metrics['top_events'], 0, 10) as $event) {
            $this->line("   {$event['event_name']}: {$event['count']}");
        }

        return Command::SUCCESS;
    }
}

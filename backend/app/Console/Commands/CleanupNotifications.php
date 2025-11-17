<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class CleanupNotifications extends Command
{
    protected $signature = 'notifications:cleanup {--days=30 : Days old to clean up}';

    protected $description = 'Clean up old read notifications';

    public function handle(NotificationService $notificationService): int
    {
        $days = (int) $this->option('days');

        $this->info("Cleaning up notifications older than {$days} days...");

        $count = $notificationService->cleanupOld($days);

        $this->info("Deleted {$count} old notifications.");

        return Command::SUCCESS;
    }
}

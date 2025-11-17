<?php

namespace App\Console\Commands;

use App\Services\CommissionService;
use Illuminate\Console\Command;

class ApproveCommissions extends Command
{
    protected $signature = 'commissions:approve {--days=7 : Number of days old to auto-approve}';

    protected $description = 'Auto-approve old commissions';

    public function handle(CommissionService $commissionService): int
    {
        $days = (int) $this->option('days');

        $this->info("Auto-approving commissions older than {$days} days...");

        $count = $commissionService->autoApprove($days);

        $this->info("Approved {$count} commissions.");

        return Command::SUCCESS;
    }
}

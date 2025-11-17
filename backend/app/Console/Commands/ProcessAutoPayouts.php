<?php

namespace App\Console\Commands;

use App\Services\PayoutService;
use Illuminate\Console\Command;

class ProcessAutoPayouts extends Command
{
    protected $signature = 'payouts:process-auto';

    protected $description = 'Process automatic payouts for merchants with auto-payout enabled';

    public function handle(PayoutService $payoutService): int
    {
        $this->info('Processing automatic payouts...');

        $count = $payoutService->processAutoPayouts();

        $this->info("Created {$count} automatic payouts.");

        return Command::SUCCESS;
    }
}

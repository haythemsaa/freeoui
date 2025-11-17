<?php

namespace App\Console\Commands;

use App\Services\BoostService;
use Illuminate\Console\Command;

class ProcessExpiredBoosts extends Command
{
    protected $signature = 'boosts:process-expired';

    protected $description = 'Mark expired boosts as completed';

    public function handle(BoostService $boostService): int
    {
        $this->info('Processing expired boosts...');

        $count = $boostService->processExpiredBoosts();

        $this->info("Processed {$count} expired boosts.");

        return Command::SUCCESS;
    }
}

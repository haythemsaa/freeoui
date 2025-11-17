<?php

namespace App\Console\Commands;

use App\Services\LoyaltyService;
use Illuminate\Console\Command;

class ExpireLoyaltyPoints extends Command
{
    protected $signature = 'loyalty:expire-points';

    protected $description = 'Expire loyalty points that have reached their expiration date';

    public function handle(LoyaltyService $loyaltyService): int
    {
        $this->info('Expiring loyalty points...');

        $count = $loyaltyService->expirePoints();

        $this->info("Expired {$count} loyalty point entries.");

        return Command::SUCCESS;
    }
}

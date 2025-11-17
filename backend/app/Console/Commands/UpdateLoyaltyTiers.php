<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\LoyaltyService;
use Illuminate\Console\Command;

class UpdateLoyaltyTiers extends Command
{
    protected $signature = 'loyalty:update-tiers';

    protected $description = 'Update loyalty tiers for all users based on their lifetime points';

    public function handle(LoyaltyService $loyaltyService): int
    {
        $this->info('Updating loyalty tiers...');

        $bar = $this->output->createProgressBar(User::count());

        $updated = 0;

        User::chunk(100, function ($users) use ($loyaltyService, $bar, &$updated) {
            foreach ($users as $user) {
                $oldTier = $user->loyalty_tier;
                $loyaltyService->updateUserTier($user);

                if ($user->fresh()->loyalty_tier !== $oldTier) {
                    $updated++;
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();

        $this->info("Updated {$updated} user tiers.");

        return Command::SUCCESS;
    }
}

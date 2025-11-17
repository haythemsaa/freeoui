<?php

namespace App\Console\Commands;

use App\Services\AchievementService;
use Illuminate\Console\Command;

class SeedAchievements extends Command
{
    protected $signature = 'achievements:seed';

    protected $description = 'Seed default achievements';

    public function handle(AchievementService $achievementService): int
    {
        $this->info('Seeding default achievements...');

        $achievementService->seedDefaultAchievements();

        $this->info('Default achievements seeded successfully.');

        return Command::SUCCESS;
    }
}

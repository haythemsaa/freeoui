<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AchievementService;
use Illuminate\Console\Command;

class CheckUserAchievements extends Command
{
    protected $signature = 'achievements:check {user_id? : The ID of a specific user to check}';

    protected $description = 'Check and unlock achievements for users';

    public function handle(AchievementService $achievementService): int
    {
        if ($userId = $this->argument('user_id')) {
            $user = User::find($userId);

            if (!$user) {
                $this->error("User not found: {$userId}");
                return Command::FAILURE;
            }

            $this->info("Checking achievements for user: {$user->name}");
            $unlocked = $achievementService->checkAchievements($user);

            $this->info("Unlocked " . count($unlocked) . " achievements.");

            foreach ($unlocked as $achievement) {
                $this->line("  - {$achievement->name}");
            }

            return Command::SUCCESS;
        }

        // Check for all users
        $this->info('Checking achievements for all users...');
        $bar = $this->output->createProgressBar(User::count());

        $totalUnlocked = 0;

        User::chunk(100, function ($users) use ($achievementService, $bar, &$totalUnlocked) {
            foreach ($users as $user) {
                $unlocked = $achievementService->checkAchievements($user);
                $totalUnlocked += count($unlocked);
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();

        $this->info("Total achievements unlocked: {$totalUnlocked}");

        return Command::SUCCESS;
    }
}

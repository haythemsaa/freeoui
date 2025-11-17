<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UpdateUserEngagement extends Command
{
    protected $signature = 'users:update-engagement';

    protected $description = 'Update user engagement scores and last active timestamps';

    public function handle(): int
    {
        $this->info('Updating user engagement metrics...');

        $count = 0;

        User::chunk(100, function ($users) use (&$count) {
            foreach ($users as $user) {
                // Update last_active_at from last session
                $lastSession = $user->sessions()->latest('started_at')->first();

                if ($lastSession) {
                    $user->update([
                        'last_active_at' => $lastSession->started_at,
                    ]);
                }

                $count++;
            }
        });

        $this->info("Updated {$count} users.");

        return Command::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateUserLevels extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'users:update-levels
                            {--user= : Update specific user ID}
                            {--dry-run : Show what would be updated without actually updating}';

    /**
     * The console command description.
     */
    protected $description = 'Update user levels based on their QR code usage';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $userId = $this->option('user');

        $this->info('Updating user levels...');

        $query = User::query();

        if ($userId) {
            $query->where('id', $userId);
        }

        $users = $query->get();

        if ($users->isEmpty()) {
            $this->warn('No users found.');
            return self::SUCCESS;
        }

        $this->info("Processing {$users->count()} users...");

        $updated = 0;
        $unchanged = 0;

        $changes = [];

        foreach ($users as $user) {
            $totalUsed = $user->qrCodes()->where('status', 'used')->count();
            $newLevel = $this->determineLevel($totalUsed);
            $oldLevel = $user->level;

            if ($newLevel !== $oldLevel) {
                $changes[] = [
                    'ID' => $user->id,
                    'Name' => $user->full_name,
                    'Old Level' => $oldLevel,
                    'New Level' => $newLevel,
                    'QR Codes Used' => $totalUsed,
                ];

                if (!$dryRun) {
                    $user->update(['level' => $newLevel]);

                    Log::info('User level updated', [
                        'user_id' => $user->id,
                        'old_level' => $oldLevel,
                        'new_level' => $newLevel,
                        'total_used' => $totalUsed,
                    ]);
                }

                $updated++;
            } else {
                $unchanged++;
            }
        }

        if (!empty($changes)) {
            $this->table(
                ['ID', 'Name', 'Old Level', 'New Level', 'QR Codes Used'],
                $changes
            );
        }

        if ($dryRun) {
            $this->warn('DRY RUN - No users were updated.');
        }

        $this->info("Updated: {$updated} | Unchanged: {$unchanged}");

        return self::SUCCESS;
    }

    /**
     * Determine user level based on QR codes used
     */
    protected function determineLevel(int $totalUsed): string
    {
        return match(true) {
            $totalUsed >= 50 => 'platinum',
            $totalUsed >= 25 => 'gold',
            $totalUsed >= 10 => 'silver',
            default => 'bronze',
        };
    }
}

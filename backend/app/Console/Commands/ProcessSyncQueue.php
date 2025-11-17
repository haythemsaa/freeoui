<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\OfflineSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessSyncQueue extends Command
{
    protected $signature = 'sync:process {--user_id= : Process for specific user}';

    protected $description = 'Process offline sync queue';

    public function handle(OfflineSyncService $syncService): int
    {
        $userId = $this->option('user_id');

        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("User not found: {$userId}");
                return Command::FAILURE;
            }

            $this->info("Processing sync queue for user {$user->name}...");
            $results = $syncService->processSyncQueue($user);
            $this->info("Processed " . count($results) . " items.");

            return Command::SUCCESS;
        }

        // Process for all users with pending items
        $this->info('Processing sync queue for all users...');

        $userIds = DB::table('sync_queue')
            ->where('status', 'pending')
            ->distinct()
            ->pluck('user_id');

        $bar = $this->output->createProgressBar($userIds->count());

        $totalProcessed = 0;

        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if ($user) {
                $results = $syncService->processSyncQueue($user);
                $totalProcessed += count($results);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Total processed: {$totalProcessed} items.");

        return Command::SUCCESS;
    }
}

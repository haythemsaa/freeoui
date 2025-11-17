<?php

namespace App\Jobs;

use App\Models\OfflineSyncQueue;
use App\Services\OfflineSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessOfflineSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;

    public function __construct(
        public OfflineSyncQueue $syncItem
    ) {}

    public function handle(OfflineSyncService $offlineSyncService): void
    {
        try {
            Log::info('Processing offline sync', [
                'sync_id' => $this->syncItem->id,
                'action_type' => $this->syncItem->action_type,
                'entity_type' => $this->syncItem->entity_type,
            ]);

            $offlineSyncService->processSyncItem($this->syncItem);

            Log::info('Offline sync processed', [
                'sync_id' => $this->syncItem->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Offline sync exception', [
                'sync_id' => $this->syncItem->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->syncItem->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
            'attempts' => $this->syncItem->attempts + 1,
        ]);

        Log::error('Offline sync job failed', [
            'sync_id' => $this->syncItem->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

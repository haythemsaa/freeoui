<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class OfflineSyncService
{
    /**
     * Queue offline action
     */
    public function queueAction(
        User $user,
        string $actionType,
        string $entityType,
        array $payload,
        ?string $clientUuid = null
    ): int {
        return DB::table('sync_queue')->insertGetId([
            'user_id' => $user->id,
            'action_type' => $actionType,
            'entity_type' => $entityType,
            'payload' => json_encode($payload),
            'status' => 'pending',
            'client_uuid' => $clientUuid,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Process sync queue
     */
    public function processSyncQueue(User $user): array
    {
        $items = DB::table('sync_queue')
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get();

        $results = [];

        foreach ($items as $item) {
            try {
                $result = $this->processItem($item);
                $results[] = $result;

                DB::table('sync_queue')
                    ->where('id', $item->id)
                    ->update([
                        'status' => 'completed',
                        'processed_at' => now(),
                        'updated_at' => now(),
                    ]);
            } catch (\Exception $e) {
                DB::table('sync_queue')
                    ->where('id', $item->id)
                    ->update([
                        'status' => 'failed',
                        'error_message' => $e->getMessage(),
                        'retry_count' => DB::raw('retry_count + 1'),
                        'updated_at' => now(),
                    ]);

                $results[] = [
                    'id' => $item->id,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Process individual sync item
     */
    protected function processItem($item): array
    {
        $payload = json_decode($item->payload, true);

        switch ($item->entity_type) {
            case 'favorite':
                return $this->syncFavorite($item->user_id, $item->action_type, $payload);

            case 'review':
                return $this->syncReview($item->user_id, $item->action_type, $payload);

            case 'booking':
                return $this->syncBooking($item->user_id, $item->action_type, $payload);

            case 'qr_scan':
                return $this->syncQRScan($item->user_id, $payload);

            default:
                throw new \Exception("Unknown entity type: {$item->entity_type}");
        }
    }

    /**
     * Sync favorite
     */
    protected function syncFavorite(int $userId, string $actionType, array $payload): array
    {
        if ($actionType === 'create') {
            DB::table('user_favorites')->insert([
                'user_id' => $userId,
                'advantage_id' => $payload['advantage_id'] ?? null,
                'merchant_id' => $payload['merchant_id'] ?? null,
                'created_at' => $payload['created_at'] ?? now(),
                'updated_at' => now(),
            ]);
        } elseif ($actionType === 'delete') {
            DB::table('user_favorites')
                ->where('user_id', $userId)
                ->where('id', $payload['id'])
                ->delete();
        }

        return ['success' => true, 'entity_type' => 'favorite'];
    }

    /**
     * Sync review
     */
    protected function syncReview(int $userId, string $actionType, array $payload): array
    {
        if ($actionType === 'create') {
            $reviewService = app(ReviewService::class);
            $user = User::find($userId);

            $reviewable = null;
            if ($payload['reviewable_type'] === 'Merchant') {
                $reviewable = \App\Models\Merchant::find($payload['reviewable_id']);
            } elseif ($payload['reviewable_type'] === 'Advantage') {
                $reviewable = \App\Models\Advantage::find($payload['reviewable_id']);
            }

            if ($reviewable) {
                $reviewService->createReview(
                    $user,
                    $reviewable,
                    $payload['rating'],
                    $payload['comment'] ?? null
                );
            }
        }

        return ['success' => true, 'entity_type' => 'review'];
    }

    /**
     * Sync booking
     */
    protected function syncBooking(int $userId, string $actionType, array $payload): array
    {
        if ($actionType === 'create') {
            $bookingService = app(BookingService::class);
            $user = User::find($userId);
            $merchant = \App\Models\Merchant::find($payload['merchant_id']);

            if ($merchant) {
                $bookingService->createBooking(
                    $user,
                    $merchant,
                    \Carbon\Carbon::parse($payload['booking_date']),
                    $payload['booking_time'],
                    $payload['party_size'],
                    null,
                    $payload['customer_info'] ?? []
                );
            }
        }

        return ['success' => true, 'entity_type' => 'booking'];
    }

    /**
     * Sync QR scan
     */
    protected function syncQRScan(int $userId, array $payload): array
    {
        // Process QR code scan that happened offline
        DB::table('qr_code_scans')->insert([
            'user_id' => $userId,
            'qr_code_id' => $payload['qr_code_id'],
            'scanned_at' => $payload['scanned_at'] ?? now(),
            'latitude' => $payload['latitude'] ?? null,
            'longitude' => $payload['longitude'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['success' => true, 'entity_type' => 'qr_scan'];
    }

    /**
     * Clean up old completed items
     */
    public function cleanup(int $daysOld = 7): int
    {
        return DB::table('sync_queue')
            ->where('status', 'completed')
            ->where('processed_at', '<', now()->subDays($daysOld))
            ->delete();
    }
}

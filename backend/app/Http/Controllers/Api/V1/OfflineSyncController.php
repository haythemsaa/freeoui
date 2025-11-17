<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\OfflineSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfflineSyncController extends Controller
{
    public function __construct(
        private OfflineSyncService $offlineSyncService
    ) {}

    /**
     * Queue offline actions for sync
     */
    public function sync(Request $request): JsonResponse
    {
        $request->validate([
            'actions' => 'required|array',
            'actions.*.action_type' => 'required|in:create,update,delete',
            'actions.*.entity_type' => 'required|in:favorite,review,booking,qr_scan',
            'actions.*.payload' => 'required|array',
            'actions.*.client_uuid' => 'nullable|string',
            'actions.*.timestamp' => 'required|date',
        ]);

        $user = $request->user();
        $results = [];

        foreach ($request->actions as $action) {
            try {
                $queueId = $this->offlineSyncService->queueAction(
                    $user,
                    $action['action_type'],
                    $action['entity_type'],
                    $action['payload'],
                    $action['client_uuid'] ?? null
                );

                $results[] = [
                    'client_uuid' => $action['client_uuid'] ?? null,
                    'queue_id' => $queueId,
                    'status' => 'queued',
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'client_uuid' => $action['client_uuid'] ?? null,
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => count($results) . ' actions en file d\'attente',
            'data' => [
                'results' => $results,
            ],
        ]);
    }

    /**
     * Process queued actions immediately
     */
    public function processQueue(Request $request): JsonResponse
    {
        $user = $request->user();

        try {
            $processed = $this->offlineSyncService->processUserQueue($user);

            return response()->json([
                'status' => 'success',
                'message' => $processed . ' actions traitées',
                'data' => [
                    'processed_count' => $processed,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get sync queue status
     */
    public function queueStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        $queue = \App\Models\OfflineSyncQueue::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing'])
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'pending_count' => $queue->where('status', 'pending')->count(),
                'processing_count' => $queue->where('status', 'processing')->count(),
                'queue_items' => $queue,
            ],
        ]);
    }

    /**
     * Retry failed sync actions
     */
    public function retryFailed(Request $request): JsonResponse
    {
        $user = $request->user();

        $failed = \App\Models\OfflineSyncQueue::where('user_id', $user->id)
            ->where('status', 'failed')
            ->get();

        foreach ($failed as $item) {
            $this->offlineSyncService->retrySyncItem($item);
        }

        return response()->json([
            'status' => 'success',
            'message' => $failed->count() . ' actions en cours de réessai',
        ]);
    }

    /**
     * Get conflict resolution for duplicate actions
     */
    public function resolveConflict(Request $request, int $queueId): JsonResponse
    {
        $request->validate([
            'resolution' => 'required|in:use_server,use_client,merge',
        ]);

        $user = $request->user();
        $queueItem = \App\Models\OfflineSyncQueue::where('user_id', $user->id)
            ->findOrFail($queueId);

        // Handle conflict resolution based on strategy
        // This is a simplified implementation
        switch ($request->resolution) {
            case 'use_server':
                $queueItem->update(['status' => 'cancelled']);
                break;
            case 'use_client':
                $this->offlineSyncService->processSyncItem($queueItem, true);
                break;
            case 'merge':
                // Complex merge logic would go here
                break;
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Conflit résolu',
        ]);
    }
}

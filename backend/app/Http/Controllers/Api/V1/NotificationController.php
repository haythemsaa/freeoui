<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Get user's notifications
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $notifications = $user->notifications()
            ->latest()
            ->paginate(30);

        // Count unread
        $unreadCount = $user->notifications()
            ->where('read_at', null)
            ->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'notifications' => $notifications->items(),
                'unread_count' => $unreadCount,
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'total_pages' => $notifications->lastPage(),
                    'total_items' => $notifications->total(),
                ],
            ],
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $notification = $user->notifications()->findOrFail($id);

        $notification->update([
            'read_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Notification marquée comme lue',
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $user->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'status' => 'success',
            'message' => 'Toutes les notifications marquées comme lues',
        ]);
    }

    /**
     * Delete notification
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $notification = $user->notifications()->findOrFail($id);

        $notification->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Notification supprimée',
        ]);
    }

    /**
     * Get notification settings
     */
    public function settings(Request $request): JsonResponse
    {
        $user = $request->user();
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'settings' => [
                    'proximity_alerts' => $user->proximity_notifications_enabled ?? true,
                    'promotional' => $user->promotional_notifications_enabled ?? true,
                    'chat' => $user->chat_notifications_enabled ?? true,
                    'system' => $user->system_notifications_enabled ?? true,
                ],
            ],
        ]);
    }

    /**
     * Update notification settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $request->validate([
            'proximity_alerts' => 'boolean',
            'promotional' => 'boolean',
            'chat' => 'boolean',
            'system' => 'boolean',
        ]);

        $user = $request->user();
        
        $user->update([
            'proximity_notifications_enabled' => $request->input('proximity_alerts', true),
            'promotional_notifications_enabled' => $request->input('promotional', true),
            'chat_notifications_enabled' => $request->input('chat', true),
            'system_notifications_enabled' => $request->input('system', true),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Préférences de notifications mises à jour',
        ]);
    }
}

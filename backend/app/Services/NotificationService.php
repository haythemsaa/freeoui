<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send notification to user
     */
    public function send(
        User $user,
        string $title,
        string $message,
        string $category = 'system',
        array $data = [],
        string $priority = 'normal',
        ?string $actionUrl = null,
        ?string $imageUrl = null
    ): Notification {
        $notification = Notification::create([
            'user_id' => $user->id,
            'notification_type' => 'push',
            'title' => $title,
            'message' => $message,
            'category' => $category,
            'priority' => $priority,
            'data' => $data,
            'action_url' => $actionUrl,
            'image_url' => $imageUrl,
        ]);

        if ($user->fcm_token) {
            $this->sendPushNotification($notification);
        }

        return $notification;
    }

    protected function sendPushNotification(Notification $notification): bool
    {
        $user = $notification->user;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . config('services.fcm.server_key'),
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'to' => $user->fcm_token,
                'notification' => [
                    'title' => $notification->title,
                    'body' => $notification->message,
                    'image' => $notification->image_url,
                ],
                'data' => array_merge($notification->data ?? [], [
                    'notification_id' => $notification->id,
                ]),
            ]);

            if ($response->successful()) {
                $notification->markAsSent(['fcm' => $response->json()]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('FCM failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function getUnread(User $user, int $limit = 20): array
    {
        return Notification::where('user_id', $user->id)
            ->unread()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}

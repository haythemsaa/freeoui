<?php

namespace App\Jobs;

use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;

    public function __construct(
        public Notification $notification
    ) {}

    public function handle(): void
    {
        $user = $this->notification->user;

        if (!$user->fcm_token) {
            Log::info('No FCM token for user', ['user_id' => $user->id]);
            return;
        }

        $fcmServerKey = config('services.fcm.server_key');

        if (!$fcmServerKey) {
            Log::error('FCM server key not configured');
            return;
        }

        $payload = [
            'to' => $user->fcm_token,
            'notification' => [
                'title' => $this->notification->title,
                'body' => $this->notification->message,
                'sound' => 'default',
                'badge' => $user->notifications()->whereNull('read_at')->count(),
            ],
            'data' => [
                'notification_id' => $this->notification->id,
                'category' => $this->notification->category,
                'action_url' => $this->notification->action_url,
                'data' => $this->notification->data,
            ],
            'priority' => $this->notification->priority === 'high' ? 'high' : 'normal',
        ];

        if ($this->notification->image_url) {
            $payload['notification']['image'] = $this->notification->image_url;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $fcmServerKey,
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', $payload);

            if ($response->successful()) {
                Log::info('Push notification sent', [
                    'notification_id' => $this->notification->id,
                    'user_id' => $user->id,
                ]);
            } else {
                Log::error('FCM push failed', [
                    'notification_id' => $this->notification->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                
                // If token is invalid, clear it
                if ($response->status() === 404 || $response->json('results.0.error') === 'InvalidRegistration') {
                    $user->update(['fcm_token' => null]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Push notification exception', [
                'notification_id' => $this->notification->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Push notification job failed', [
            'notification_id' => $this->notification->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

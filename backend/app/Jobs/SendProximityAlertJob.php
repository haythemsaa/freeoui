<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Advantage;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class SendProximityAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user,
        public Advantage $advantage,
        public float $distanceMeters,
        public float $relevanceScore
    ) {}

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService): void
    {
        try {
            // Send push notification
            $title = "Nouvelle offre à proximité!";
            $body = "{$this->advantage->title} - À " . round($this->distanceMeters) . "m de vous";

            $data = [
                'type' => 'proximity_alert',
                'advantage_id' => $this->advantage->id,
                'merchant_id' => $this->advantage->merchant_id,
                'distance_meters' => $this->distanceMeters,
                'relevance_score' => $this->relevanceScore,
            ];

            $notificationService->sendPushNotification(
                $this->user,
                $title,
                $body,
                $data
            );

            Log::info('Proximity alert sent', [
                'user_id' => $this->user->id,
                'advantage_id' => $this->advantage->id,
                'distance' => $this->distanceMeters,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send proximity alert', [
                'user_id' => $this->user->id,
                'advantage_id' => $this->advantage->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}

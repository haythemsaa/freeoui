<?php

namespace App\Listeners;

use App\Events\QRCodeGenerated;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendQRCodeGeneratedNotification implements ShouldQueue
{
    protected NotificationService $notificationService;

    /**
     * Create the event listener.
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(QRCodeGenerated $event): void
    {
        try {
            $advantage = $event->qrCode->advantage;

            $title = 'QR Code généré';
            $body = "Votre QR code pour '{$advantage->title}' est prêt à être utilisé";

            $data = [
                'type' => 'qr_code_generated',
                'qr_code_id' => $event->qrCode->id,
                'advantage_id' => $advantage->id,
            ];

            $this->notificationService->sendPushNotification(
                $event->user,
                $title,
                $body,
                $data
            );

            Log::info('QR code generated notification sent', [
                'user_id' => $event->user->id,
                'qr_code_id' => $event->qrCode->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send QR code generated notification', [
                'error' => $e->getMessage(),
                'user_id' => $event->user->id,
            ]);
        }
    }
}

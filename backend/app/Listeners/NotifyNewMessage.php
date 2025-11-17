<?php

namespace App\Listeners;

use App\Events\MessageSent;
use App\Services\NotificationService;

class NotifyNewMessage
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function handle(MessageSent $event): void
    {
        $message = $event->message;
        $conversation = $message->conversation;

        // Determine recipient based on sender
        if ($message->sender_type === 'user') {
            // Notify merchant
            if ($conversation->merchant) {
                // Merchant notification logic would go here
            }
        } else {
            // Notify user
            $user = $conversation->user;

            if ($user && $user->chat_notifications_enabled) {
                $senderName = $message->sender->full_name ?? 'Support';

                $this->notificationService->send(
                    $user,
                    "💬 Nouveau message de {$senderName}",
                    substr($message->message, 0, 100),
                    'chat',
                    [
                        'conversation_id' => $conversation->id,
                        'message_id' => $message->id,
                    ],
                    'normal',
                    "/chat/conversations/{$conversation->id}"
                );
            }
        }
    }
}

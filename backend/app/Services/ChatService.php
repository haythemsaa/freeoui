<?php

namespace App\Services;

use App\Models\User;
use App\Models\Merchant;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\DB;

class ChatService
{
    /**
     * Create or get conversation
     */
    public function getOrCreateConversation(
        string $conversationType,
        ?User $user = null,
        ?Merchant $merchant = null,
        ?string $subject = null
    ): Conversation {
        $query = Conversation::where('conversation_type', $conversationType);

        if ($user) {
            $query->where('user_id', $user->id);
        }

        if ($merchant) {
            $query->where('merchant_id', $merchant->id);
        }

        $conversation = $query->whereIn('status', ['open', 'in_progress'])->first();

        if ($conversation) {
            return $conversation;
        }

        return Conversation::create([
            'conversation_type' => $conversationType,
            'user_id' => $user?->id,
            'merchant_id' => $merchant?->id,
            'subject' => $subject,
            'status' => 'open',
            'priority' => 'normal',
        ]);
    }

    /**
     * Send message
     */
    public function sendMessage(
        Conversation $conversation,
        User $sender,
        string $message,
        string $senderType = 'user',
        string $messageType = 'text',
        array $attachments = []
    ): Message {
        return DB::transaction(function () use ($conversation, $sender, $message, $senderType, $messageType, $attachments) {
            $messageModel = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'sender_type' => $senderType,
                'message' => $message,
                'message_type' => $messageType,
                'attachments' => $attachments,
            ]);

            // Update conversation
            $conversation->update([
                'last_message_at' => now(),
                'last_message_by' => $sender->id,
            ]);

            // Increment unread counts
            if ($senderType === 'user') {
                $conversation->increment('unread_count_merchant');
                $conversation->increment('unread_count_admin');
            } elseif ($senderType === 'merchant') {
                $conversation->increment('unread_count_user');
                $conversation->increment('unread_count_admin');
            } elseif ($senderType === 'admin') {
                $conversation->increment('unread_count_user');
                $conversation->increment('unread_count_merchant');
            }

            // Send real-time notification via websockets/pusher
            $this->broadcastMessage($conversation, $messageModel);

            // Send push notification if recipient offline
            $this->sendPushNotification($conversation, $messageModel, $senderType);

            return $messageModel;
        });
    }

    /**
     * Mark conversation as read
     */
    public function markAsRead(Conversation $conversation, string $readerType): void
    {
        $conversation->markAsRead($readerType);

        // Mark all messages as read
        Message::where('conversation_id', $conversation->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Get conversation history
     */
    public function getConversationHistory(Conversation $conversation, int $limit = 50, int $offset = 0): array
    {
        $messages = Message::where('conversation_id', $conversation->id)
            ->orderByDesc('created_at')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        return $messages->toArray();
    }

    /**
     * Broadcast message via WebSocket/Pusher
     */
    protected function broadcastMessage(Conversation $conversation, Message $message): void
    {
        // TODO: Implement Pusher/WebSocket broadcast
        // Example: broadcast(new MessageSent($message))->toOthers();
    }

    /**
     * Send push notification for offline users
     */
    protected function sendPushNotification(Conversation $conversation, Message $message, string $senderType): void
    {
        $notificationService = app(NotificationService::class);

        if ($senderType === 'user' && $conversation->merchant_id) {
            // Notify merchant
            $merchant = $conversation->merchant;
            if ($merchant->owner) {
                $notificationService->send(
                    $merchant->owner,
                    'New Message',
                    $message->message,
                    'chat',
                    ['conversation_id' => $conversation->id]
                );
            }
        } elseif ($senderType === 'merchant' && $conversation->user_id) {
            // Notify user
            $notificationService->send(
                $conversation->user,
                'New Message from ' . $conversation->merchant->business_name,
                $message->message,
                'chat',
                ['conversation_id' => $conversation->id]
            );
        }
    }

    /**
     * Get user's conversations
     */
    public function getUserConversations(User $user, string $status = null): array
    {
        $query = Conversation::where('user_id', $user->id)
            ->orderByDesc('last_message_at');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->with(['merchant', 'lastMessageSender'])->get()->toArray();
    }

    /**
     * Get merchant's conversations
     */
    public function getMerchantConversations(Merchant $merchant, string $status = null): array
    {
        $query = Conversation::where('merchant_id', $merchant->id)
            ->orderByDesc('last_message_at');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->with(['user', 'lastMessageSender'])->get()->toArray();
    }

    /**
     * Automated bot response
     */
    public function handleBotResponse(Conversation $conversation, string $userMessage): ?Message
    {
        // TODO: Implement AI/NLP intent matching
        // For now, simple keyword matching

        $keywords = [
            'bonjour' => 'Bonjour! Comment puis-je vous aider aujourd\'hui?',
            'aide' => 'Que puis-je faire pour vous? Vous pouvez me poser des questions sur les avantages, les réservations ou votre compte.',
            'merci' => 'Je vous en prie! N\'hésitez pas si vous avez d\'autres questions.',
        ];

        foreach ($keywords as $keyword => $response) {
            if (stripos($userMessage, $keyword) !== false) {
                // Create bot user or system user
                $botUser = User::where('email', 'bot@freeoui.tn')->first();

                if ($botUser) {
                    return $this->sendMessage($conversation, $botUser, $response, 'bot', 'text');
                }

                break;
            }
        }

        return null;
    }

    /**
     * Calculate average response time for merchant
     */
    public function updateMerchantResponseTime(Merchant $merchant): void
    {
        $conversations = Conversation::where('merchant_id', $merchant->id)
            ->where('status', '!=', 'open')
            ->get();

        $totalTime = 0;
        $count = 0;

        foreach ($conversations as $conversation) {
            $firstUserMessage = Message::where('conversation_id', $conversation->id)
                ->where('sender_type', 'user')
                ->orderBy('created_at')
                ->first();

            $firstMerchantMessage = Message::where('conversation_id', $conversation->id)
                ->where('sender_type', 'merchant')
                ->where('created_at', '>', $firstUserMessage?->created_at)
                ->orderBy('created_at')
                ->first();

            if ($firstUserMessage && $firstMerchantMessage) {
                $diff = $firstMerchantMessage->created_at->diffInMinutes($firstUserMessage->created_at);
                $totalTime += $diff;
                $count++;
            }
        }

        if ($count > 0) {
            $merchant->update([
                'chat_response_time_avg' => $totalTime / $count,
            ]);
        }
    }
}

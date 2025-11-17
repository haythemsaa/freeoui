<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(
        private ChatService $chatService
    ) {}

    /**
     * Get user's conversations
     */
    public function conversations(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $conversations = $user->conversations()
            ->with(['merchant', 'lastMessage'])
            ->latest('updated_at')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => [
                'conversations' => $conversations->items(),
                'pagination' => [
                    'current_page' => $conversations->currentPage(),
                    'total_pages' => $conversations->lastPage(),
                    'total_items' => $conversations->total(),
                ],
            ],
        ]);
    }

    /**
     * Get conversation messages
     */
    public function messages(Request $request, int $conversationId): JsonResponse
    {
        $user = $request->user();
        
        $conversation = $user->conversations()->findOrFail($conversationId);
        
        $messages = $conversation->messages()
            ->with(['sender'])
            ->latest()
            ->paginate(50);

        // Mark as read
        $conversation->update([
            'unread_count_user' => 0,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'conversation' => $conversation,
                'messages' => array_reverse($messages->items()),
                'pagination' => [
                    'current_page' => $messages->currentPage(),
                    'total_pages' => $messages->lastPage(),
                    'total_items' => $messages->total(),
                ],
            ],
        ]);
    }

    /**
     * Send a message
     */
    public function sendMessage(Request $request, int $conversationId): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:2000',
            'message_type' => 'nullable|in:text,image,file',
            'attachments' => 'nullable|array',
        ]);

        $user = $request->user();
        $conversation = $user->conversations()->findOrFail($conversationId);

        try {
            $message = $this->chatService->sendMessage(
                $conversation,
                $user,
                $request->message,
                'user',
                $request->input('message_type', 'text'),
                $request->input('attachments', [])
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Message envoyé',
                'data' => [
                    'message' => $message,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Start a conversation with a merchant
     */
    public function startConversation(Request $request): JsonResponse
    {
        $request->validate([
            'merchant_id' => 'required|exists:merchants,id',
            'message' => 'required|string|max:2000',
        ]);

        $user = $request->user();

        try {
            // Check if conversation already exists
            $conversation = Conversation::where('user_id', $user->id)
                ->where('merchant_id', $request->merchant_id)
                ->where('conversation_type', 'user_merchant')
                ->first();

            if (!$conversation) {
                $conversation = $this->chatService->createConversation(
                    'user_merchant',
                    $user->id,
                    $request->merchant_id
                );
            }

            $message = $this->chatService->sendMessage(
                $conversation,
                $user,
                $request->message,
                'user'
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Conversation créée',
                'data' => [
                    'conversation' => $conversation->fresh(),
                    'message' => $message,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Mark conversation as read
     */
    public function markAsRead(Request $request, int $conversationId): JsonResponse
    {
        $user = $request->user();
        $conversation = $user->conversations()->findOrFail($conversationId);

        $conversation->update([
            'unread_count_user' => 0,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Conversation marquée comme lue',
        ]);
    }
}

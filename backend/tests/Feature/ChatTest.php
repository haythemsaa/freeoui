<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_conversations()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/v1/chat/conversations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'conversations',
                    'pagination',
                ],
            ]);
    }

    public function test_user_can_start_conversation_with_merchant()
    {
        $user = User::factory()->create();
        $merchant = Merchant::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/chat/conversations', [
                'merchant_id' => $merchant->id,
                'message' => 'Hello, I have a question.',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'conversation',
                    'message',
                ],
            ]);
    }

    public function test_user_can_send_message_in_conversation()
    {
        $user = User::factory()->create();
        $merchant = Merchant::factory()->create();
        
        $conversation = Conversation::create([
            'conversation_type' => 'user_merchant',
            'user_id' => $user->id,
            'merchant_id' => $merchant->id,
            'status' => 'open',
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/v1/chat/conversations/{$conversation->id}/messages", [
                'message' => 'This is a test message.',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'message',
                ],
            ]);
    }
}

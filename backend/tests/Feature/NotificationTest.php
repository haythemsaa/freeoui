<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_notifications()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/v1/notifications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'notifications',
                    'unread_count',
                    'pagination',
                ],
            ]);
    }

    public function test_user_can_mark_notification_as_read()
    {
        $user = User::factory()->create();
        $notification = Notification::create([
            'user_id' => $user->id,
            'title' => 'Test Notification',
            'message' => 'Test message',
            'category' => 'system',
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertStatus(200);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_can_mark_all_notifications_as_read()
    {
        $user = User::factory()->create();
        
        Notification::create([
            'user_id' => $user->id,
            'title' => 'Test 1',
            'message' => 'Message 1',
            'category' => 'system',
        ]);

        Notification::create([
            'user_id' => $user->id,
            'title' => 'Test 2',
            'message' => 'Message 2',
            'category' => 'system',
        ]);

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/notifications/read-all');

        $response->assertStatus(200);
        $this->assertEquals(0, $user->notifications()->whereNull('read_at')->count());
    }

    public function test_user_can_update_notification_settings()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->putJson('/api/v1/notifications/settings', [
                'proximity_alerts' => false,
                'promotional' => true,
                'chat' => true,
                'system' => true,
            ]);

        $response->assertStatus(200);
    }
}

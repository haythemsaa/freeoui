<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'wallet_balance' => 100.00,
            'is_premium' => false,
        ]);
    }

    /** @test */
    public function user_can_get_subscription_plans()
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/subscriptions/plans');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'plans' => [
                    'monthly' => ['amount', 'duration', 'label'],
                    'quarterly' => ['amount', 'duration', 'label'],
                    'yearly' => ['amount', 'duration', 'label'],
                ],
            ]);

        $this->assertEquals(9.90, $response->json('plans.monthly.amount'));
        $this->assertEquals(24.90, $response->json('plans.quarterly.amount'));
        $this->assertEquals(89.90, $response->json('plans.yearly.amount'));
    }

    /** @test */
    public function user_can_subscribe_to_monthly_plan()
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/subscriptions/subscribe', [
                'plan_type' => 'monthly',
                'payment_method' => 'wallet',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'subscription' => ['id', 'plan_type', 'status', 'amount'],
                'payment',
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('monthly', $response->json('subscription.plan_type'));
        $this->assertEquals('active', $response->json('subscription.status'));

        // Verify user is now premium
        $this->assertTrue($this->user->fresh()->is_premium);

        // Verify subscription exists
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $this->user->id,
            'plan_type' => 'monthly',
            'status' => 'active',
        ]);
    }

    /** @test */
    public function user_cannot_subscribe_twice()
    {
        // Create active subscription
        Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
            'ends_at' => now()->addMonth(),
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/subscriptions/subscribe', [
                'plan_type' => 'monthly',
                'payment_method' => 'wallet',
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function user_can_get_current_subscription()
    {
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
            'plan_type' => 'monthly',
            'ends_at' => now()->addMonth(),
        ]);

        $this->user->update(['is_premium' => true]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/subscriptions/current');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'is_premium' => true,
                'subscription' => [
                    'id' => $subscription->id,
                    'plan_type' => 'monthly',
                    'status' => 'active',
                ],
            ]);
    }

    /** @test */
    public function user_can_cancel_subscription()
    {
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
            'ends_at' => now()->addMonth(),
        ]);

        $this->user->update(['is_premium' => true]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/subscriptions/cancel', [
                'reason' => 'Too expensive',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Subscription cancelled successfully',
            ]);

        // Verify subscription is cancelled
        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => 'cancelled',
            'cancellation_reason' => 'Too expensive',
        ]);

        // Verify user is no longer premium
        $this->assertFalse($this->user->fresh()->is_premium);
    }

    /** @test */
    public function user_can_view_subscription_history()
    {
        // Create multiple subscriptions
        Subscription::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/subscriptions/history');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'subscriptions' => [
                    '*' => ['id', 'plan_type', 'status', 'amount', 'created_at'],
                ],
            ]);

        $this->assertCount(3, $response->json('subscriptions'));
    }

    /** @test */
    public function premium_user_receives_discount()
    {
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
            'discount_percentage' => 15.0,
            'ends_at' => now()->addMonth(),
        ]);

        $originalPrice = 100.00;
        $discountedPrice = $subscription->applyDiscount($originalPrice);

        $this->assertEquals(85.00, $discountedPrice);
    }
}

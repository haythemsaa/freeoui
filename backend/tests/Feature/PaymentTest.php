<?php

namespace Tests\Feature;

use App\Models\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_payment_history()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/v1/payments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'payments',
                    'pagination',
                ],
            ]);
    }

    public function test_user_can_create_payment()
    {
        $user = User::factory()->create();
        $merchant = Merchant::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/payments', [
                'payment_type' => 'wallet_topup',
                'payment_provider' => 'd17',
                'amount' => 50,
                'merchant_id' => $merchant->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'payment',
                ],
            ]);
    }

    public function test_payment_requires_valid_provider()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/payments', [
                'payment_type' => 'wallet_topup',
                'payment_provider' => 'invalid_provider',
                'amount' => 50,
            ]);

        $response->assertStatus(422);
    }
}

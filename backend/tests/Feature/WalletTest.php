<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_wallet_balance()
    {
        $user = User::factory()->create();
        Wallet::create([
            'user_id' => $user->id,
            'balance' => 100.0,
            'currency' => 'TND',
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/v1/wallet');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'wallet' => ['balance', 'currency', 'status'],
                    'transactions',
                ],
            ]);
    }

    public function test_user_can_initiate_wallet_topup()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/wallet/top-up', [
                'amount' => 50,
                'payment_provider' => 'd17',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'payment' => ['payment_number', 'amount', 'status'],
                ],
            ]);
    }

    public function test_topup_requires_minimum_amount()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/wallet/top-up', [
                'amount' => 5, // Below minimum of 10
                'payment_provider' => 'd17',
            ]);

        $response->assertStatus(422);
    }
}

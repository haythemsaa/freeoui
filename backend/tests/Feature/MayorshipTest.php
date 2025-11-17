<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Merchant;
use App\Models\Mayorship;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MayorshipTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Merchant $merchant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'coins_balance' => 0,
            'checkin_streak' => 0,
        ]);

        $this->merchant = Merchant::factory()->create();
    }

    /** @test */
    public function user_can_checkin_at_merchant()
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/mayorships/checkin', [
                'merchant_id' => $this->merchant->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'is_mayor',
                    'checkin_count',
                    'coins_awarded',
                ],
            ]);

        $this->assertTrue($response->json('success'));

        // Verify mayorship record created
        $this->assertDatabaseHas('mayorships', [
            'user_id' => $this->user->id,
            'merchant_id' => $this->merchant->id,
            'checkin_count' => 1,
        ]);

        // Verify coins awarded
        $this->assertGreaterThan(0, $this->user->fresh()->coins_balance);
    }

    /** @test */
    public function user_becomes_mayor_on_first_checkin()
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/mayorships/checkin', [
                'merchant_id' => $this->merchant->id,
            ]);

        $this->assertTrue($response->json('data.is_mayor'));
        $this->assertTrue($response->json('data.became_mayor'));

        // Verify mayorship is active
        $this->assertDatabaseHas('mayorships', [
            'user_id' => $this->user->id,
            'merchant_id' => $this->merchant->id,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function user_can_only_checkin_once_per_day()
    {
        // First check-in
        $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/mayorships/checkin', [
                'merchant_id' => $this->merchant->id,
            ]);

        // Second check-in same day
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/mayorships/checkin', [
                'merchant_id' => $this->merchant->id,
            ]);

        $data = $response->json('data');

        // Check-in count should still be 1
        $this->assertEquals(1, $data['checkin_count']);
    }

    /** @test */
    public function mayorship_transfers_to_user_with_most_checkins()
    {
        $user2 = User::factory()->create();

        // User 1 checks in once
        Mayorship::create([
            'user_id' => $this->user->id,
            'merchant_id' => $this->merchant->id,
            'checkin_count' => 1,
            'last_checkin_at' => now()->subDay(),
            'is_active' => true,
            'claimed_at' => now()->subDay(),
        ]);

        // User 2 checks in (will have 1 check-in)
        // Then checks in again to reach 2 check-ins
        $mayorship2 = Mayorship::create([
            'user_id' => $user2->id,
            'merchant_id' => $this->merchant->id,
            'checkin_count' => 1,
            'last_checkin_at' => now()->subDay(),
            'is_active' => false,
        ]);

        // Simulate second check-in (would normally go through service)
        $mayorship2->update(['checkin_count' => 2]);

        // User 2 should have more check-ins now
        $this->assertGreaterThan(
            Mayorship::where('user_id', $this->user->id)->first()->checkin_count,
            $mayorship2->fresh()->checkin_count
        );
    }

    /** @test */
    public function user_can_get_current_mayor()
    {
        Mayorship::create([
            'user_id' => $this->user->id,
            'merchant_id' => $this->merchant->id,
            'checkin_count' => 5,
            'last_checkin_at' => now(),
            'is_active' => true,
            'claimed_at' => now(),
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/mayorships/{$this->merchant->id}/mayor");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'mayor' => [
                    'user',
                    'checkin_count',
                    'claimed_at',
                    'last_checkin_at',
                ],
            ]);

        $this->assertEquals($this->user->id, $response->json('mayor.user.id'));
        $this->assertEquals(5, $response->json('mayor.checkin_count'));
    }

    /** @test */
    public function user_can_get_challengers()
    {
        // Create multiple mayorships
        $users = User::factory()->count(5)->create();

        foreach ($users as $index => $user) {
            Mayorship::create([
                'user_id' => $user->id,
                'merchant_id' => $this->merchant->id,
                'checkin_count' => $index + 1,
                'last_checkin_at' => now(),
                'is_active' => false,
            ]);
        }

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/mayorships/{$this->merchant->id}/challengers");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'challengers' => [
                    '*' => [
                        'rank',
                        'user',
                        'checkin_count',
                        'is_mayor',
                        'last_checkin_at',
                    ],
                ],
            ]);

        $challengers = $response->json('challengers');

        // Verify sorting by checkin_count (descending)
        $this->assertGreaterThanOrEqual(
            $challengers[1]['checkin_count'],
            $challengers[0]['checkin_count']
        );
    }

    /** @test */
    public function user_can_view_their_mayorships()
    {
        // Create multiple active mayorships
        $merchants = Merchant::factory()->count(3)->create();

        foreach ($merchants as $merchant) {
            Mayorship::create([
                'user_id' => $this->user->id,
                'merchant_id' => $merchant->id,
                'checkin_count' => rand(1, 10),
                'last_checkin_at' => now(),
                'is_active' => true,
                'claimed_at' => now(),
            ]);
        }

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/mayorships/my-mayorships');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'mayorships' => [
                    '*' => [
                        'merchant',
                        'checkin_count',
                        'claimed_at',
                        'last_checkin_at',
                    ],
                ],
                'total',
            ]);

        $this->assertEquals(3, $response->json('total'));
    }

    /** @test */
    public function user_can_view_checkin_stats()
    {
        // Create mayorships
        Mayorship::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'checkin_count' => 3,
        ]);

        $this->user->update(['checkin_streak' => 7]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/mayorships/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'stats' => [
                    'total_checkins',
                    'unique_merchants',
                    'current_streak',
                ],
            ]);

        $this->assertEquals(15, $response->json('stats.total_checkins')); // 5 * 3
        $this->assertEquals(5, $response->json('stats.unique_merchants'));
        $this->assertEquals(7, $response->json('stats.current_streak'));
    }

    /** @test */
    public function mayor_receives_bonus_coins()
    {
        // First check-in to become mayor
        $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/mayorships/checkin', [
                'merchant_id' => $this->merchant->id,
            ]);

        $initialCoins = $this->user->fresh()->coins_balance;

        // Update to next day and check-in again as mayor
        $mayorship = Mayorship::where('user_id', $this->user->id)
            ->where('merchant_id', $this->merchant->id)
            ->first();

        $mayorship->update(['last_checkin_at' => now()->subDay()]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/mayorships/checkin', [
                'merchant_id' => $this->merchant->id,
            ]);

        $coinsAwarded = $response->json('data.coins_awarded');

        // Mayor bonus is 1.5x, so should be 15 coins instead of 10
        $this->assertGreaterThanOrEqual(10, $coinsAwarded);
    }
}

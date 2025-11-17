<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Governorate;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaderboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'points_balance' => 1000,
            'level' => 5,
        ]);
    }

    /** @test */
    public function user_can_get_global_leaderboard()
    {
        // Create multiple users with different points
        User::factory()->count(10)->create([
            'points_balance' => rand(100, 5000),
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/leaderboards/global?period=weekly&limit=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'period',
                'leaderboard' => [
                    '*' => [
                        'rank',
                        'user' => ['id', 'first_name', 'last_name', 'points_balance', 'level'],
                    ],
                ],
                'user_rank',
            ]);

        $this->assertEquals('weekly', $response->json('period'));
        $this->assertTrue($response->json('user_rank') > 0);
    }

    /** @test */
    public function global_leaderboard_is_sorted_by_points()
    {
        // Create users with specific points
        User::factory()->create(['points_balance' => 5000]);
        User::factory()->create(['points_balance' => 3000]);
        User::factory()->create(['points_balance' => 4000]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/leaderboards/global');

        $leaderboard = $response->json('leaderboard');

        // Verify sorting
        $this->assertGreaterThanOrEqual(
            $leaderboard[1]['user']['points_balance'],
            $leaderboard[0]['user']['points_balance']
        );
    }

    /** @test */
    public function user_can_get_friends_leaderboard()
    {
        // Create friends
        $friend1 = User::factory()->create(['points_balance' => 2000]);
        $friend2 = User::factory()->create(['points_balance' => 1500]);

        // Add friends (assuming a friends relationship exists)
        // $this->user->friends()->attach([$friend1->id, $friend2->id]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/leaderboards/friends');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'leaderboard' => [
                    '*' => [
                        'rank',
                        'user' => ['id', 'points_balance'],
                    ],
                ],
            ]);
    }

    /** @test */
    public function user_can_get_governorate_leaderboard()
    {
        $governorate = Governorate::factory()->create();

        // Create users in the same governorate
        User::factory()->count(5)->create([
            'governorate_id' => $governorate->id,
            'points_balance' => rand(100, 2000),
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/leaderboards/governorate?governorate_id=' . $governorate->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'governorate_id',
                'leaderboard' => [
                    '*' => [
                        'rank',
                        'user',
                    ],
                ],
            ]);

        $this->assertEquals($governorate->id, $response->json('governorate_id'));
    }

    /** @test */
    public function user_can_get_category_leaderboard()
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/leaderboards/category?category_id=' . $category->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'category_id',
                'leaderboard',
            ]);

        $this->assertEquals($category->id, $response->json('category_id'));
    }

    /** @test */
    public function user_can_get_their_rank()
    {
        // Create users with higher points
        User::factory()->count(5)->create([
            'points_balance' => 5000,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/leaderboards/rank?period=weekly');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'period',
                'rank',
                'points',
                'level',
            ]);

        $this->assertEquals('weekly', $response->json('period'));
        $this->assertEquals($this->user->points_balance, $response->json('points'));
        $this->assertEquals($this->user->level, $response->json('level'));
        $this->assertGreaterThan(1, $response->json('rank'));
    }

    /** @test */
    public function leaderboard_respects_limit_parameter()
    {
        User::factory()->count(50)->create([
            'points_balance' => rand(100, 5000),
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/leaderboards/global?limit=20');

        $leaderboard = $response->json('leaderboard');

        $this->assertLessThanOrEqual(20, count($leaderboard));
    }

    /** @test */
    public function leaderboard_supports_different_periods()
    {
        $periods = ['weekly', 'monthly', 'all_time'];

        foreach ($periods as $period) {
            $response = $this->actingAs($this->user, 'api')
                ->getJson("/api/v1/leaderboards/global?period={$period}");

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'period' => $period,
                ]);
        }
    }
}

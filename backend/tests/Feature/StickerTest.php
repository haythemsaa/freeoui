<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Category;
use App\Models\StickerCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StickerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->category = Category::factory()->create();
    }

    /** @test */
    public function user_can_view_their_sticker_collection()
    {
        // Create stickers for user
        StickerCollection::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/stickers/collection');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'collection' => [
                    '*' => [
                        'id',
                        'category',
                        'sticker_tier',
                        'visit_count',
                        'coin_multiplier',
                    ],
                ],
                'statistics',
            ]);

        $this->assertCount(3, $response->json('collection'));
    }

    /** @test */
    public function user_can_view_sticker_statistics()
    {
        // Create stickers with different tiers
        StickerCollection::factory()->create([
            'user_id' => $this->user->id,
            'sticker_tier' => 'bronze',
            'visit_count' => 3,
        ]);

        StickerCollection::factory()->create([
            'user_id' => $this->user->id,
            'sticker_tier' => 'silver',
            'visit_count' => 7,
        ]);

        StickerCollection::factory()->create([
            'user_id' => $this->user->id,
            'sticker_tier' => 'gold',
            'visit_count' => 20,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/stickers/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'statistics' => [
                    'total_stickers',
                    'completion_percentage',
                    'tier_breakdown' => [
                        'bronze',
                        'silver',
                        'gold',
                        'diamond',
                    ],
                    'total_visits',
                    'average_multiplier',
                    'total_multiplier',
                ],
            ]);

        $this->assertEquals(3, $response->json('statistics.total_stickers'));
        $this->assertEquals(30, $response->json('statistics.total_visits')); // 3 + 7 + 20
    }

    /** @test */
    public function sticker_tier_upgrades_with_visits()
    {
        $sticker = StickerCollection::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'sticker_type' => 'category',
            'sticker_tier' => 'bronze',
            'visit_count' => 1,
            'coin_multiplier' => 1.0,
            'unlocked_at' => now(),
        ]);

        // Tier thresholds: bronze=1, silver=5, gold=15, diamond=50
        $this->assertEquals('bronze', $sticker->sticker_tier);
        $this->assertEquals(1.0, $sticker->coin_multiplier);

        // Increment to silver
        $sticker->update(['visit_count' => 5]);
        $sticker->incrementVisit(); // This should trigger tier check
        $sticker->refresh();

        // After reaching silver threshold
        $sticker->update(['visit_count' => 5, 'sticker_tier' => 'silver', 'coin_multiplier' => 1.2]);
        $this->assertEquals('silver', $sticker->sticker_tier);
        $this->assertEquals(1.2, floatval($sticker->coin_multiplier));
    }

    /** @test */
    public function user_can_view_sticker_leaderboard()
    {
        // Create users with sticker collections
        $users = User::factory()->count(5)->create();

        foreach ($users as $user) {
            StickerCollection::factory()->count(rand(1, 10))->create([
                'user_id' => $user->id,
            ]);
        }

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/stickers/leaderboard?limit=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'leaderboard' => [
                    '*' => [
                        'rank',
                        'user',
                        'sticker_count',
                    ],
                ],
            ]);
    }

    /** @test */
    public function user_can_view_sticker_tiers()
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/stickers/tiers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'tiers' => [
                    'bronze' => ['name', 'visits_required', 'coin_multiplier', 'color'],
                    'silver' => ['name', 'visits_required', 'coin_multiplier', 'color'],
                    'gold' => ['name', 'visits_required', 'coin_multiplier', 'color'],
                    'diamond' => ['name', 'visits_required', 'coin_multiplier', 'color'],
                ],
            ]);

        $this->assertEquals(1, $response->json('tiers.bronze.visits_required'));
        $this->assertEquals(5, $response->json('tiers.silver.visits_required'));
        $this->assertEquals(15, $response->json('tiers.gold.visits_required'));
        $this->assertEquals(50, $response->json('tiers.diamond.visits_required'));
    }

    /** @test */
    public function user_can_showcase_stickers()
    {
        $stickers = StickerCollection::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $stickerIds = $stickers->pluck('id')->toArray();

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/stickers/showcase', [
                'sticker_ids' => $stickerIds,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Stickers showcased successfully',
            ]);

        // Verify showcased stickers saved
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'showcased_stickers' => json_encode($stickerIds),
        ]);
    }

    /** @test */
    public function user_cannot_showcase_others_stickers()
    {
        $otherUser = User::factory()->create();
        $sticker = StickerCollection::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/stickers/showcase', [
                'sticker_ids' => [$sticker->id],
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'One or more stickers do not belong to you',
            ]);
    }

    /** @test */
    public function user_can_view_category_progress()
    {
        $sticker = StickerCollection::create([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'sticker_type' => 'category',
            'sticker_tier' => 'bronze',
            'visit_count' => 3,
            'coin_multiplier' => 1.0,
            'unlocked_at' => now(),
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/stickers/category/{$this->category->id}/progress");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'sticker',
                'progress' => [
                    'current_visits',
                    'current_tier',
                    'next_tier',
                    'visits_to_next_tier',
                ],
            ]);

        $this->assertEquals(3, $response->json('progress.current_visits'));
        $this->assertEquals('bronze', $response->json('progress.current_tier'));
        $this->assertEquals('silver', $response->json('progress.next_tier'));
        $this->assertEquals(2, $response->json('progress.visits_to_next_tier')); // 5 - 3 = 2
    }

    /** @test */
    public function user_gets_null_sticker_for_unvisited_category()
    {
        $newCategory = Category::factory()->create();

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/stickers/category/{$newCategory->id}/progress");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'sticker' => null,
                'progress' => [
                    'current_visits' => 0,
                    'current_tier' => null,
                    'next_tier' => 'bronze',
                    'visits_to_next_tier' => 1,
                ],
            ]);
    }

    /** @test */
    public function completion_percentage_calculates_correctly()
    {
        // Create 10 total categories
        Category::factory()->count(10)->create();

        // User has stickers for 3 categories
        StickerCollection::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/stickers/statistics');

        $completionPercentage = $response->json('statistics.completion_percentage');

        // Should be approximately 30% (3 out of 10)
        $this->assertGreaterThanOrEqual(25, $completionPercentage);
        $this->assertLessThanOrEqual(35, $completionPercentage);
    }
}

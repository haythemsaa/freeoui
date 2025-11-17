<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Challenge;
use App\Models\ChallengeParticipation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChallengeTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'points_balance' => 100,
        ]);
    }

    /** @test */
    public function user_can_view_active_challenges()
    {
        // Create active challenges
        Challenge::factory()->count(3)->create([
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addWeek(),
        ]);

        // Create inactive challenge
        Challenge::factory()->create([
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/challenges');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'challenges' => [
                    '*' => [
                        'challenge',
                        'participation',
                        'time_remaining',
                    ],
                ],
            ]);

        // Should only show 3 active challenges
        $this->assertCount(3, $response->json('challenges'));
    }

    /** @test */
    public function user_can_view_specific_challenge()
    {
        $challenge = Challenge::factory()->create([
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addWeek(),
            'reward_points' => 500,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/challenges/{$challenge->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'challenge',
                'participation',
                'leaderboard',
            ]);

        $this->assertEquals($challenge->id, $response->json('challenge.id'));
        $this->assertEquals(500, $response->json('challenge.reward_points'));
    }

    /** @test */
    public function user_can_participate_in_challenge()
    {
        $challenge = Challenge::factory()->create([
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addWeek(),
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/v1/challenges/{$challenge->id}/participate");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Successfully joined challenge',
            ]);

        // Verify participation created
        $this->assertDatabaseHas('challenge_participations', [
            'user_id' => $this->user->id,
            'challenge_id' => $challenge->id,
            'status' => 'active',
        ]);
    }

    /** @test */
    public function user_cannot_participate_in_expired_challenge()
    {
        $challenge = Challenge::factory()->create([
            'is_active' => false,
            'starts_at' => now()->subWeek(),
            'ends_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/v1/challenges/{$challenge->id}/participate");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'This challenge is not active',
            ]);
    }

    /** @test */
    public function user_can_view_challenge_history()
    {
        $challenges = Challenge::factory()->count(5)->create();

        // Create participations with different statuses
        foreach ($challenges as $index => $challenge) {
            ChallengeParticipation::factory()->create([
                'user_id' => $this->user->id,
                'challenge_id' => $challenge->id,
                'status' => $index % 2 === 0 ? 'completed' : 'active',
            ]);
        }

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/challenges/user/history');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'history' => [
                    '*' => [
                        'id',
                        'challenge',
                        'status',
                        'progress_percentage',
                    ],
                ],
            ]);

        $this->assertCount(5, $response->json('history'));
    }

    /** @test */
    public function user_can_filter_history_by_status()
    {
        $challenge1 = Challenge::factory()->create();
        $challenge2 = Challenge::factory()->create();

        ChallengeParticipation::factory()->create([
            'user_id' => $this->user->id,
            'challenge_id' => $challenge1->id,
            'status' => 'completed',
        ]);

        ChallengeParticipation::factory()->create([
            'user_id' => $this->user->id,
            'challenge_id' => $challenge2->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/challenges/user/history?status=completed');

        $response->assertStatus(200);

        $history = $response->json('history');

        $this->assertCount(1, $history);
        $this->assertEquals('completed', $history[0]['status']);
    }

    /** @test */
    public function user_can_view_challenge_leaderboard()
    {
        $challenge = Challenge::factory()->create();

        // Create completed participations
        $users = User::factory()->count(5)->create();

        foreach ($users as $user) {
            ChallengeParticipation::factory()->create([
                'user_id' => $user->id,
                'challenge_id' => $challenge->id,
                'status' => 'completed',
                'completed_at' => now()->subMinutes(rand(1, 60)),
            ]);
        }

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/challenges/{$challenge->id}/leaderboard");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'challenge',
                'leaderboard' => [
                    '*' => [
                        'rank',
                        'user',
                        'completed_at',
                        'progress_percentage',
                    ],
                ],
            ]);

        $this->assertCount(5, $response->json('leaderboard'));
    }

    /** @test */
    public function user_can_view_challenge_progress()
    {
        $challenge = Challenge::factory()->create([
            'criteria' => ['target' => 10],
        ]);

        $participation = ChallengeParticipation::create([
            'user_id' => $this->user->id,
            'challenge_id' => $challenge->id,
            'progress' => ['current' => 5, 'target' => 10],
            'progress_percentage' => 50,
            'status' => 'active',
            'started_at' => now(),
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/challenges/{$challenge->id}/progress");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'challenge',
                'participation',
                'progress' => [
                    'percentage',
                    'current',
                    'target',
                    'status',
                ],
            ]);

        $this->assertEquals(50, $response->json('progress.percentage'));
        $this->assertEquals(5, $response->json('progress.current'));
        $this->assertEquals(10, $response->json('progress.target'));
    }

    /** @test */
    public function user_can_view_challenge_statistics()
    {
        $challenges = Challenge::factory()->count(10)->create([
            'reward_points' => 500,
        ]);

        // Create participations
        foreach ($challenges as $index => $challenge) {
            ChallengeParticipation::factory()->create([
                'user_id' => $this->user->id,
                'challenge_id' => $challenge->id,
                'status' => $index < 7 ? 'completed' : 'active',
            ]);
        }

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/challenges/user/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'statistics' => [
                    'total_challenges',
                    'completed_challenges',
                    'active_challenges',
                    'completion_rate',
                    'total_points_earned',
                ],
            ]);

        $this->assertEquals(10, $response->json('statistics.total_challenges'));
        $this->assertEquals(7, $response->json('statistics.completed_challenges'));
        $this->assertEquals(3, $response->json('statistics.active_challenges'));
        $this->assertEquals(70, $response->json('statistics.completion_rate')); // 7/10 = 70%
        $this->assertEquals(3500, $response->json('statistics.total_points_earned')); // 7 * 500
    }

    /** @test */
    public function challenge_completion_awards_points()
    {
        $challenge = Challenge::factory()->create([
            'criteria' => ['target' => 10],
            'reward_points' => 1000,
        ]);

        $participation = ChallengeParticipation::create([
            'user_id' => $this->user->id,
            'challenge_id' => $challenge->id,
            'progress' => ['current' => 9, 'target' => 10],
            'progress_percentage' => 90,
            'status' => 'active',
            'started_at' => now(),
        ]);

        $initialPoints = $this->user->points_balance;

        // Update progress to complete
        $participation->updateProgress(['current' => 10, 'target' => 10]);

        $this->assertTrue($participation->fresh()->isCompleted());
        $this->assertEquals('completed', $participation->fresh()->status);
    }

    /** @test */
    public function challenge_progress_percentage_calculates_correctly()
    {
        $participation = ChallengeParticipation::create([
            'user_id' => $this->user->id,
            'challenge_id' => Challenge::factory()->create()->id,
            'progress' => ['current' => 0, 'target' => 0],
            'progress_percentage' => 0,
            'status' => 'active',
            'started_at' => now(),
        ]);

        // Test various progress levels
        $participation->updateProgress(['current' => 5, 'target' => 10]);
        $this->assertEquals(50, $participation->fresh()->progress_percentage);

        $participation->updateProgress(['current' => 7, 'target' => 10]);
        $this->assertEquals(70, $participation->fresh()->progress_percentage);

        $participation->updateProgress(['current' => 10, 'target' => 10]);
        $this->assertEquals(100, $participation->fresh()->progress_percentage);
    }

    /** @test */
    public function challenge_progress_never_exceeds_100_percent()
    {
        $participation = ChallengeParticipation::create([
            'user_id' => $this->user->id,
            'challenge_id' => Challenge::factory()->create()->id,
            'progress' => ['current' => 0, 'target' => 10],
            'progress_percentage' => 0,
            'status' => 'active',
            'started_at' => now(),
        ]);

        // Try to set progress beyond target
        $participation->updateProgress(['current' => 15, 'target' => 10]);

        $this->assertLessThanOrEqual(100, $participation->fresh()->progress_percentage);
    }
}

<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ProximityAlertService;
use App\Models\User;
use App\Models\Advantage;
use App\Models\ProximityPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProximityAlertServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProximityAlertService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProximityAlertService();
    }

    /** @test */
    public function it_calculates_relevance_score_correctly()
    {
        // Create a test advantage
        $advantage = Advantage::factory()->create([
            'discount_percentage' => 20,
            'usage_count' => 50,
        ]);

        $advantage->merchant->rating = 4.5;
        $advantage->merchant->save();

        // Test with close proximity (100m)
        $score = $this->service->calculateRelevanceScore($advantage, 36.8065, 10.1815, 100);

        // Score should be high for close proximity and good discount
        $this->assertGreaterThan(50, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    /** @test */
    public function it_respects_daily_alert_limit()
    {
        $user = User::factory()->create();

        ProximityPreference::create([
            'user_id' => $user->id,
            'alerts_enabled' => true,
            'max_alerts_per_day' => 3,
            'proximity_radius_meters' => 1000,
        ]);

        // Create alert logs for today
        for ($i = 0; $i < 3; $i++) {
            \DB::table('proximity_alerts_log')->insert([
                'user_id' => $user->id,
                'advantage_id' => 1,
                'sent_at' => now(),
                'created_at' => now(),
            ]);
        }

        $canSend = $this->service->canSendAlert($user, 1);

        $this->assertFalse($canSend);
    }

    /** @test */
    public function it_respects_minimum_interval_between_alerts()
    {
        $user = User::factory()->create();

        ProximityPreference::create([
            'user_id' => $user->id,
            'alerts_enabled' => true,
            'min_interval_minutes' => 30,
        ]);

        // Create alert 15 minutes ago
        \DB::table('proximity_alerts_log')->insert([
            'user_id' => $user->id,
            'advantage_id' => 1,
            'sent_at' => now()->subMinutes(15),
            'created_at' => now()->subMinutes(15),
        ]);

        $canSend = $this->service->canSendAlert($user, 1);

        $this->assertFalse($canSend);
    }

    /** @test */
    public function it_filters_advantages_by_user_preferences()
    {
        $user = User::factory()->create();

        ProximityPreference::create([
            'user_id' => $user->id,
            'alerts_enabled' => true,
            'category_ids' => [1, 2, 3],
            'min_discount_percentage' => 15,
        ]);

        $validAdvantage = Advantage::factory()->create([
            'category_id' => 1,
            'discount_percentage' => 20,
        ]);

        $invalidAdvantage = Advantage::factory()->create([
            'category_id' => 4,
            'discount_percentage' => 10,
        ]);

        // Test filtering logic
        $this->assertTrue($this->service->matchesUserPreferences($user, $validAdvantage));
        $this->assertFalse($this->service->matchesUserPreferences($user, $invalidAdvantage));
    }
}

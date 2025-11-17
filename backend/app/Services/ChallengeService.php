<?php

namespace App\Services;

use App\Models\User;
use App\Models\Challenge;
use App\Models\ChallengeParticipation;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class ChallengeService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Get active challenges for user
     */
    public function getActiveChallenges(User $user): Collection
    {
        $challenges = Challenge::active()->get();

        return $challenges->map(function ($challenge) use ($user) {
            $participation = $this->getOrCreateParticipation($user, $challenge);

            return [
                'challenge' => $challenge,
                'participation' => $participation,
            ];
        });
    }

    /**
     * Get or create participation
     */
    public function getOrCreateParticipation(User $user, Challenge $challenge): ChallengeParticipation
    {
        return ChallengeParticipation::firstOrCreate(
            [
                'user_id' => $user->id,
                'challenge_id' => $challenge->id,
            ],
            [
                'progress' => ['current' => 0, 'target' => $challenge->criteria['target'] ?? 0],
                'progress_percentage' => 0,
                'status' => 'active',
                'started_at' => now(),
            ]
        );
    }

    /**
     * Update challenge progress
     */
    public function updateProgress(User $user, string $eventType, array $eventData = []): void
    {
        $challenges = Challenge::active()
            ->where('challenge_type', $eventType)
            ->get();

        foreach ($challenges as $challenge) {
            $participation = $this->getOrCreateParticipation($user, $challenge);

            if ($participation->isCompleted()) {
                continue;
            }

            $newProgress = $this->calculateProgress($challenge, $participation, $eventData);
            $previousPercentage = $participation->progress_percentage;

            $participation->updateProgress($newProgress);

            // Check if just completed
            if (!$participation->wasRecentlyCreated && $participation->isCompleted() && $previousPercentage < 100) {
                $this->awardReward($user, $challenge);
            }
        }
    }

    /**
     * Calculate new progress based on event
     */
    private function calculateProgress(Challenge $challenge, ChallengeParticipation $participation, array $eventData): array
    {
        $progress = $participation->progress;
        $criteria = $challenge->criteria;

        switch ($challenge->challenge_type) {
            case 'visit_count':
                $progress['current'] = ($progress['current'] ?? 0) + 1;
                $progress['target'] = $criteria['target'] ?? 0;
                break;

            case 'spend_amount':
                $progress['current'] = ($progress['current'] ?? 0) + ($eventData['amount'] ?? 0);
                $progress['target'] = $criteria['target'] ?? 0;
                break;

            case 'category_explore':
                $visited = $progress['visited_categories'] ?? [];
                if (isset($eventData['category_id']) && !in_array($eventData['category_id'], $visited)) {
                    $visited[] = $eventData['category_id'];
                }
                $progress['visited_categories'] = $visited;
                $progress['current'] = count($visited);
                $progress['target'] = $criteria['target'] ?? 0;
                break;

            case 'governorate_explore':
                $visited = $progress['visited_governorates'] ?? [];
                if (isset($eventData['governorate_id']) && !in_array($eventData['governorate_id'], $visited)) {
                    $visited[] = $eventData['governorate_id'];
                }
                $progress['visited_governorates'] = $visited;
                $progress['current'] = count($visited);
                $progress['target'] = $criteria['target'] ?? 0;
                break;

            case 'checkin_streak':
                $progress['current'] = $eventData['streak'] ?? 0;
                $progress['target'] = $criteria['target'] ?? 0;
                break;

            default:
                break;
        }

        return $progress;
    }

    /**
     * Award challenge completion reward
     */
    private function awardReward(User $user, Challenge $challenge): void
    {
        // Award points
        $user->increment('points_balance', $challenge->reward_points);

        // Award coins (10% of points)
        $coinsAwarded = (int) ($challenge->reward_points / 10);
        $user->increment('coins_balance', $coinsAwarded);

        // Send notification
        $this->notificationService->send(
            $user,
            '🏆 Challenge terminé !',
            "Félicitations ! Vous avez complété \"{$challenge->name}\" et gagné {$challenge->reward_points} points",
            'challenge',
            [
                'challenge_id' => $challenge->id,
                'points_awarded' => $challenge->reward_points,
                'coins_awarded' => $coinsAwarded,
            ],
            'high'
        );
    }

    /**
     * Get user challenge history
     */
    public function getUserHistory(User $user, ?string $status = null): Collection
    {
        return ChallengeParticipation::where('user_id', $user->id)
            ->when($status, function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->with('challenge')
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * Get challenge leaderboard
     */
    public function getChallengeLeaderboard(Challenge $challenge, int $limit = 50): Collection
    {
        return ChallengeParticipation::where('challenge_id', $challenge->id)
            ->where('status', 'completed')
            ->with('user')
            ->orderBy('completed_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Create new challenge (admin function)
     */
    public function createChallenge(array $data): Challenge
    {
        return Challenge::create([
            'name' => $data['name'],
            'description' => $data['description'],
            'challenge_type' => $data['challenge_type'],
            'criteria' => $data['criteria'],
            'reward_points' => $data['reward_points'],
            'badge_icon' => $data['badge_icon'] ?? null,
            'starts_at' => $data['starts_at'] ?? now(),
            'ends_at' => $data['ends_at'],
            'is_active' => $data['is_active'] ?? true,
            'is_recurring' => $data['is_recurring'] ?? false,
            'recurrence_type' => $data['recurrence_type'] ?? null,
        ]);
    }

    /**
     * Expire old challenges
     */
    public function expireChallenges(): int
    {
        $expiredCount = Challenge::where('is_active', true)
            ->where('ends_at', '<', now())
            ->update(['is_active' => false]);

        // Mark participations as expired
        ChallengeParticipation::whereHas('challenge', function ($query) {
            $query->where('is_active', false);
        })
        ->where('status', 'active')
        ->update(['status' => 'expired']);

        return $expiredCount;
    }

    /**
     * Create recurring challenges
     */
    public function createRecurringChallenges(): int
    {
        $recurringChallenges = Challenge::where('is_recurring', true)
            ->where('is_active', false)
            ->where('ends_at', '<', now())
            ->get();

        $created = 0;

        foreach ($recurringChallenges as $oldChallenge) {
            $duration = $oldChallenge->starts_at->diffInDays($oldChallenge->ends_at);

            $newChallenge = Challenge::create([
                'name' => $oldChallenge->name,
                'description' => $oldChallenge->description,
                'challenge_type' => $oldChallenge->challenge_type,
                'criteria' => $oldChallenge->criteria,
                'reward_points' => $oldChallenge->reward_points,
                'badge_icon' => $oldChallenge->badge_icon,
                'starts_at' => now(),
                'ends_at' => now()->addDays($duration),
                'is_active' => true,
                'is_recurring' => true,
                'recurrence_type' => $oldChallenge->recurrence_type,
            ]);

            $created++;
        }

        return $created;
    }
}

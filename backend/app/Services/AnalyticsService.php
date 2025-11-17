<?php

namespace App\Services;

use App\Models\User;
use App\Models\AnalyticsEvent;
use App\Models\UserSession;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsService
{
    /**
     * Track event
     */
    public function trackEvent(
        string $eventName,
        ?User $user = null,
        string $eventCategory = 'engagement',
        array $properties = [],
        ?string $sessionId = null,
        ?string $screenName = null,
        string $platform = 'web'
    ): AnalyticsEvent {
        return AnalyticsEvent::create([
            'user_id' => $user?->id,
            'event_name' => $eventName,
            'event_category' => $eventCategory,
            'properties' => $properties,
            'screen_name' => $screenName,
            'platform' => $platform,
            'session_id' => $sessionId,
        ]);
    }

    /**
     * Start user session
     */
    public function startSession(
        ?User $user,
        string $platform,
        array $deviceInfo = []
    ): UserSession {
        $sessionId = uniqid('session_', true);

        return UserSession::create([
            'session_id' => $sessionId,
            'user_id' => $user?->id,
            'started_at' => now(),
            'platform' => $platform,
            'app_version' => $deviceInfo['app_version'] ?? null,
            'device_model' => $deviceInfo['device_model'] ?? null,
            'os_version' => $deviceInfo['os_version'] ?? null,
            'metadata' => $deviceInfo,
        ]);
    }

    /**
     * End user session
     */
    public function endSession(string $sessionId): void
    {
        $session = UserSession::where('session_id', $sessionId)->first();

        if ($session) {
            $session->endSession();
        }
    }

    /**
     * Get user engagement metrics
     */
    public function getUserEngagement(User $user, Carbon $startDate, Carbon $endDate): array
    {
        $sessions = UserSession::where('user_id', $user->id)
            ->whereBetween('started_at', [$startDate, $endDate])
            ->get();

        $events = AnalyticsEvent::where('user_id', $user->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        return [
            'total_sessions' => $sessions->count(),
            'total_duration_minutes' => $sessions->sum('duration_seconds') / 60,
            'avg_session_duration_minutes' => $sessions->avg('duration_seconds') / 60,
            'total_events' => $events->count(),
            'screens_viewed' => $sessions->sum('screens_viewed'),
            'actions_performed' => $sessions->sum('actions_performed'),
            'most_visited_screens' => $this->getMostVisitedScreens($user, $startDate, $endDate),
            'most_common_events' => $this->getMostCommonEvents($user, $startDate, $endDate),
        ];
    }

    /**
     * Get most visited screens
     */
    protected function getMostVisitedScreens(User $user, Carbon $startDate, Carbon $endDate, int $limit = 5): array
    {
        return AnalyticsEvent::where('user_id', $user->id)
            ->where('event_name', 'screen_view')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select('screen_name', DB::raw('count(*) as views'))
            ->groupBy('screen_name')
            ->orderByDesc('views')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get most common events
     */
    protected function getMostCommonEvents(User $user, Carbon $startDate, Carbon $endDate, int $limit = 10): array
    {
        return AnalyticsEvent::where('user_id', $user->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select('event_name', 'event_category', DB::raw('count(*) as count'))
            ->groupBy('event_name', 'event_category')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get platform-wide analytics
     */
    public function getPlatformMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $users = User::whereBetween('created_at', [$startDate, $endDate])->count();
        $sessions = UserSession::whereBetween('started_at', [$startDate, $endDate])->count();
        $events = AnalyticsEvent::whereBetween('created_at', [$startDate, $endDate])->count();

        $dau = User::whereDate('last_active_at', now())->count();
        $wau = User::where('last_active_at', '>=', now()->subDays(7))->count();
        $mau = User::where('last_active_at', '>=', now()->subDays(30))->count();

        return [
            'new_users' => $users,
            'total_sessions' => $sessions,
            'total_events' => $events,
            'dau' => $dau,
            'wau' => $wau,
            'mau' => $mau,
            'dau_mau_ratio' => $mau > 0 ? ($dau / $mau) * 100 : 0,
            'avg_sessions_per_user' => $mau > 0 ? $sessions / $mau : 0,
            'top_platforms' => $this->getTopPlatforms($startDate, $endDate),
            'top_events' => $this->getTopEvents($startDate, $endDate),
        ];
    }

    /**
     * Get top platforms
     */
    protected function getTopPlatforms(Carbon $startDate, Carbon $endDate): array
    {
        return UserSession::whereBetween('started_at', [$startDate, $endDate])
            ->select('platform', DB::raw('count(*) as sessions'))
            ->groupBy('platform')
            ->orderByDesc('sessions')
            ->get()
            ->toArray();
    }

    /**
     * Get top events
     */
    protected function getTopEvents(Carbon $startDate, Carbon $endDate, int $limit = 20): array
    {
        return AnalyticsEvent::whereBetween('created_at', [$startDate, $endDate])
            ->select('event_name', DB::raw('count(*) as count'))
            ->groupBy('event_name')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get conversion funnel
     */
    public function getConversionFunnel(array $steps, Carbon $startDate, Carbon $endDate): array
    {
        $funnelData = [];

        foreach ($steps as $index => $step) {
            $count = AnalyticsEvent::where('event_name', $step)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->distinct('user_id')
                ->count('user_id');

            $dropoff = 0;
            if ($index > 0 && $funnelData[$index - 1]['count'] > 0) {
                $dropoff = (($funnelData[$index - 1]['count'] - $count) / $funnelData[$index - 1]['count']) * 100;
            }

            $funnelData[] = [
                'step' => $step,
                'count' => $count,
                'dropoff_percentage' => round($dropoff, 2),
            ];
        }

        return $funnelData;
    }

    /**
     * Get retention cohort analysis
     */
    public function getRetentionCohort(Carbon $startDate, int $weeks = 12): array
    {
        // Group users by registration week
        $cohorts = [];

        for ($i = 0; $i < $weeks; $i++) {
            $cohortStart = $startDate->copy()->addWeeks($i);
            $cohortEnd = $cohortStart->copy()->addWeek();

            $users = User::whereBetween('created_at', [$cohortStart, $cohortEnd])->pluck('id');

            if ($users->isEmpty()) {
                continue;
            }

            $cohortData = [
                'cohort' => $cohortStart->format('Y-m-d'),
                'size' => $users->count(),
                'retention' => [],
            ];

            // Calculate retention for each subsequent week
            for ($week = 0; $week <= min(12, $weeks - $i); $week++) {
                $retentionStart = $cohortStart->copy()->addWeeks($week);
                $retentionEnd = $retentionStart->copy()->addWeek();

                $activeUsers = UserSession::whereIn('user_id', $users)
                    ->whereBetween('started_at', [$retentionStart, $retentionEnd])
                    ->distinct('user_id')
                    ->count('user_id');

                $cohortData['retention'][$week] = [
                    'week' => $week,
                    'active_users' => $activeUsers,
                    'percentage' => round(($activeUsers / $cohortData['size']) * 100, 2),
                ];
            }

            $cohorts[] = $cohortData;
        }

        return $cohorts;
    }
}

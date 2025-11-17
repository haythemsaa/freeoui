<?php

namespace App\Services;

use App\Models\User;
use App\Models\Story;
use App\Models\StoryView;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class StoryService
{
    /**
     * Create a new story
     */
    public function createStory(User $user, array $data): Story
    {
        $story = Story::create([
            'user_id' => $user->id,
            'merchant_id' => $data['merchant_id'] ?? null,
            'type' => $data['type'] ?? 'image',
            'media_url' => $data['media_url'],
            'thumbnail_url' => $data['thumbnail_url'] ?? null,
            'caption' => $data['caption'] ?? null,
            'duration' => $data['duration'] ?? 5,
            'background_color' => $data['background_color'] ?? '#000000',
            'expires_at' => now()->addHours(24),
            'is_active' => true,
        ]);

        // Award points for creating story
        $user->increment('points_balance', 50);

        return $story;
    }

    /**
     * Get active stories feed
     */
    public function getStoriesFeed(User $user, ?int $merchantId = null): Collection
    {
        $query = Story::where('is_active', true)
            ->where('expires_at', '>', now())
            ->with(['user', 'merchant']);

        if ($merchantId) {
            $query->where('merchant_id', $merchantId);
        } else {
            // Show stories from friends and followed merchants
            $friendIds = $user->friends()->pluck('id');
            $followedMerchantIds = $user->followedMerchants()->pluck('id');

            $query->where(function ($q) use ($friendIds, $followedMerchantIds, $user) {
                $q->whereIn('user_id', $friendIds)
                    ->orWhereIn('merchant_id', $followedMerchantIds)
                    ->orWhere('user_id', $user->id);
            });
        }

        $stories = $query->orderByDesc('created_at')->get();

        // Group stories by user/merchant
        return $stories->groupBy(function ($story) {
            return $story->merchant_id ? "merchant_{$story->merchant_id}" : "user_{$story->user_id}";
        })->map(function ($userStories) use ($user) {
            $firstStory = $userStories->first();
            $hasViewed = $userStories->every(function ($story) use ($user) {
                return $story->views()->where('user_id', $user->id)->exists();
            });

            return [
                'owner_type' => $firstStory->merchant_id ? 'merchant' : 'user',
                'owner' => $firstStory->merchant ?? $firstStory->user,
                'stories' => $userStories,
                'has_viewed' => $hasViewed,
                'latest_at' => $userStories->max('created_at'),
            ];
        })->sortByDesc('has_viewed')->values();
    }

    /**
     * Record story view
     */
    public function recordView(User $user, Story $story): StoryView
    {
        // Check if already viewed
        $existingView = StoryView::where('user_id', $user->id)
            ->where('story_id', $story->id)
            ->first();

        if ($existingView) {
            return $existingView;
        }

        $view = StoryView::create([
            'user_id' => $user->id,
            'story_id' => $story->id,
            'viewed_at' => now(),
        ]);

        // Increment view count
        $story->increment('views_count');

        return $view;
    }

    /**
     * Delete story
     */
    public function deleteStory(Story $story): bool
    {
        // Delete media file
        if ($story->media_url) {
            Storage::disk('public')->delete($story->media_url);
        }

        if ($story->thumbnail_url) {
            Storage::disk('public')->delete($story->thumbnail_url);
        }

        return $story->delete();
    }

    /**
     * Get story analytics
     */
    public function getStoryAnalytics(Story $story): array
    {
        $views = $story->views()->with('user')->get();

        return [
            'total_views' => $views->count(),
            'unique_viewers' => $views->unique('user_id')->count(),
            'completion_rate' => $this->calculateCompletionRate($story),
            'viewers' => $views->map(function ($view) {
                return [
                    'user' => $view->user,
                    'viewed_at' => $view->viewed_at,
                ];
            }),
        ];
    }

    /**
     * Calculate story completion rate
     */
    private function calculateCompletionRate(Story $story): float
    {
        $totalViews = $story->views_count;
        if ($totalViews === 0) {
            return 0;
        }

        // Assuming views that lasted > 80% of duration are "completed"
        $completedViews = StoryView::where('story_id', $story->id)
            ->where('watch_duration', '>=', $story->duration * 0.8)
            ->count();

        return round(($completedViews / $totalViews) * 100, 2);
    }

    /**
     * Expire old stories
     */
    public function expireOldStories(): int
    {
        return Story::where('is_active', true)
            ->where('expires_at', '<', now())
            ->update(['is_active' => false]);
    }
}

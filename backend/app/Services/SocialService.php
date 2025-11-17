<?php

namespace App\Services;

use App\Models\User;
use App\Models\SocialShare;
use Illuminate\Database\Eloquent\Model;

class SocialService
{
    /**
     * Create social share
     */
    public function createShare(
        User $user,
        Model $shareable,
        string $platform,
        string $shareType
    ): SocialShare {
        $shareUrl = $this->generateShareUrl($shareable, $user);
        $shareText = $this->generateShareText($shareable, $shareType);
        $shareImage = $this->getShareImage($shareable);

        $share = SocialShare::create([
            'user_id' => $user->id,
            'shareable_type' => get_class($shareable),
            'shareable_id' => $shareable->id,
            'platform' => $platform,
            'share_type' => $shareType,
            'share_url' => $shareUrl,
            'share_text' => $shareText,
            'share_image_url' => $shareImage,
        ]);

        // Award points for sharing
        if (app()->has(LoyaltyService::class)) {
            app(LoyaltyService::class)->awardPoints(
                $user,
                10,
                'Social share',
                $share,
                now()->addYear()
            );
        }

        return $share;
    }

    /**
     * Generate share URL
     */
    protected function generateShareUrl(Model $shareable, User $user): string
    {
        $baseUrl = config('app.url');

        if ($shareable instanceof \App\Models\Advantage) {
            return "{$baseUrl}/advantages/{$shareable->id}?ref={$user->referral_code}";
        } elseif ($shareable instanceof \App\Models\Merchant) {
            return "{$baseUrl}/merchants/{$shareable->id}?ref={$user->referral_code}";
        }

        return $baseUrl;
    }

    /**
     * Generate share text
     */
    protected function generateShareText(Model $shareable, string $shareType): string
    {
        if ($shareable instanceof \App\Models\Advantage) {
            return "🎁 Découvrez cette super offre sur FreeOui: {$shareable->title}! " .
                   "{$shareable->getDiscountDisplay()} - Ne ratez pas cette opportunité!";
        } elseif ($shareable instanceof \App\Models\Merchant) {
            return "📍 J'ai découvert {$shareable->business_name} sur FreeOui! " .
                   "Des offres exclusives à proximité de vous.";
        }

        return "Découvrez FreeOui - Les meilleures offres près de chez vous!";
    }

    /**
     * Get share image
     */
    protected function getShareImage(Model $shareable): ?string
    {
        if ($shareable instanceof \App\Models\Advantage) {
            return $shareable->main_image_url;
        } elseif ($shareable instanceof \App\Models\Merchant) {
            return $shareable->logo_url ?? $shareable->cover_image_url;
        }

        return null;
    }

    /**
     * Track share click
     */
    public function trackClick(string $shareUrl): void
    {
        $share = SocialShare::where('share_url', $shareUrl)->first();

        if ($share) {
            $share->incrementClicks();
        }
    }

    /**
     * Track share conversion
     */
    public function trackConversion(string $shareUrl): void
    {
        $share = SocialShare::where('share_url', $shareUrl)->first();

        if ($share) {
            $share->incrementConversions();

            // Award bonus points for successful conversion
            if (app()->has(LoyaltyService::class)) {
                app(LoyaltyService::class)->awardBonusPoints(
                    $share->user,
                    50,
                    'Share conversion bonus'
                );
            }
        }
    }

    /**
     * Get user's share stats
     */
    public function getUserStats(User $user): array
    {
        $shares = SocialShare::where('user_id', $user->id)->get();

        return [
            'total_shares' => $shares->count(),
            'total_clicks' => $shares->sum('click_count'),
            'total_conversions' => $shares->sum('conversion_count'),
            'avg_conversion_rate' => $shares->avg(function ($share) {
                return $share->getConversionRate();
            }),
            'by_platform' => $this->getSharesByPlatform($user),
            'top_shared_items' => $this->getTopSharedItems($user),
        ];
    }

    /**
     * Get shares by platform
     */
    protected function getSharesByPlatform(User $user): array
    {
        return SocialShare::where('user_id', $user->id)
            ->selectRaw('platform, count(*) as count, sum(click_count) as clicks')
            ->groupBy('platform')
            ->get()
            ->toArray();
    }

    /**
     * Get top shared items
     */
    protected function getTopSharedItems(User $user, int $limit = 5): array
    {
        return SocialShare::where('user_id', $user->id)
            ->selectRaw('shareable_type, shareable_id, count(*) as shares, sum(click_count) as clicks')
            ->groupBy('shareable_type', 'shareable_id')
            ->orderByDesc('shares')
            ->limit($limit)
            ->with('shareable')
            ->get()
            ->toArray();
    }
}

<?php

namespace App\Services;

use App\Models\User;
use App\Models\Review;
use App\Models\ReviewHelpfulness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReviewService
{
    /**
     * Create a review
     */
    public function createReview(
        User $user,
        Model $reviewable,
        int $rating,
        ?string $comment = null,
        array $photos = []
    ): Review {
        // Check if user already reviewed this
        $existingReview = Review::where('user_id', $user->id)
            ->where('reviewable_type', get_class($reviewable))
            ->where('reviewable_id', $reviewable->id)
            ->first();

        if ($existingReview) {
            throw new \Exception('You have already reviewed this');
        }

        return DB::transaction(function () use ($user, $reviewable, $rating, $comment, $photos) {
            // Upload photos if any
            $uploadedPhotos = [];
            foreach ($photos as $photo) {
                $path = Storage::disk('public')->put('reviews', $photo);
                $uploadedPhotos[] = $path;
            }

            // Create review
            $review = Review::create([
                'user_id' => $user->id,
                'reviewable_type' => get_class($reviewable),
                'reviewable_id' => $reviewable->id,
                'rating' => $rating,
                'comment' => $comment,
                'photos' => $uploadedPhotos,
                'is_verified' => $this->canVerifyReview($user, $reviewable),
                'is_approved' => true, // Auto-approve for now
            ]);

            // Update reviewable's rating
            $this->updateReviewableRating($reviewable);

            // Award points for review
            if (app()->has(LoyaltyService::class)) {
                app(LoyaltyService::class)->awardPoints(
                    $user,
                    50,
                    'Review submitted',
                    $review,
                    now()->addYear()
                );
            }

            return $review;
        });
    }

    /**
     * Update a review
     */
    public function updateReview(
        Review $review,
        int $rating,
        ?string $comment = null,
        array $newPhotos = []
    ): Review {
        return DB::transaction(function () use ($review, $rating, $comment, $newPhotos) {
            // Upload new photos
            $uploadedPhotos = $review->photos ?? [];
            foreach ($newPhotos as $photo) {
                $path = Storage::disk('public')->put('reviews', $photo);
                $uploadedPhotos[] = $path;
            }

            $review->update([
                'rating' => $rating,
                'comment' => $comment,
                'photos' => $uploadedPhotos,
            ]);

            // Update reviewable's rating
            $this->updateReviewableRating($review->reviewable);

            return $review;
        });
    }

    /**
     * Delete a review
     */
    public function deleteReview(Review $review): void
    {
        DB::transaction(function () use ($review) {
            $reviewable = $review->reviewable;

            // Delete photos
            if ($review->photos) {
                foreach ($review->photos as $photo) {
                    Storage::disk('public')->delete($photo);
                }
            }

            $review->delete();

            // Update reviewable's rating
            $this->updateReviewableRating($reviewable);
        });
    }

    /**
     * Add merchant response to review
     */
    public function addMerchantResponse(Review $review, string $response): Review
    {
        $review->update([
            'merchant_response' => $response,
            'merchant_responded_at' => now(),
        ]);

        return $review;
    }

    /**
     * Vote on review helpfulness
     */
    public function voteHelpfulness(Review $review, User $user, bool $isHelpful): ReviewHelpfulness
    {
        // Check if user already voted
        $existingVote = ReviewHelpfulness::where('review_id', $review->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingVote) {
            // Update vote if different
            if ($existingVote->is_helpful !== $isHelpful) {
                DB::transaction(function () use ($review, $existingVote, $isHelpful) {
                    // Update counts
                    if ($existingVote->is_helpful) {
                        $review->decrement('helpful_count');
                        $review->increment('not_helpful_count');
                    } else {
                        $review->decrement('not_helpful_count');
                        $review->increment('helpful_count');
                    }

                    $existingVote->update(['is_helpful' => $isHelpful]);
                });
            }

            return $existingVote;
        }

        return DB::transaction(function () use ($review, $user, $isHelpful) {
            $vote = ReviewHelpfulness::create([
                'review_id' => $review->id,
                'user_id' => $user->id,
                'is_helpful' => $isHelpful,
            ]);

            // Update counts
            if ($isHelpful) {
                $review->increment('helpful_count');
            } else {
                $review->increment('not_helpful_count');
            }

            return $vote;
        });
    }

    /**
     * Update reviewable's average rating
     */
    protected function updateReviewableRating(Model $reviewable): void
    {
        $stats = Review::where('reviewable_type', get_class($reviewable))
            ->where('reviewable_id', $reviewable->id)
            ->approved()
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as count')
            ->first();

        $reviewable->update([
            'rating_average' => $stats->avg_rating ?? 0,
            'rating_count' => $stats->count ?? 0,
        ]);
    }

    /**
     * Check if review can be verified
     */
    protected function canVerifyReview(User $user, Model $reviewable): bool
    {
        // Check if user has actually used this merchant/advantage
        // For now, check if they have scanned QR codes from this merchant
        if (method_exists($reviewable, 'merchant')) {
            $merchant = $reviewable->merchant ?? $reviewable;

            return DB::table('qr_code_scans')
                ->join('qr_codes', 'qr_code_scans.qr_code_id', '=', 'qr_codes.id')
                ->where('qr_code_scans.user_id', $user->id)
                ->where('qr_codes.merchant_id', $merchant->id)
                ->exists();
        }

        return false;
    }

    /**
     * Get review statistics for reviewable
     */
    public function getReviewStats(Model $reviewable): array
    {
        $reviews = Review::where('reviewable_type', get_class($reviewable))
            ->where('reviewable_id', $reviewable->id)
            ->approved();

        $totalReviews = $reviews->count();
        $averageRating = $reviews->avg('rating') ?? 0;

        // Rating distribution
        $distribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $count = $reviews->clone()->where('rating', $i)->count();
            $distribution[$i] = [
                'count' => $count,
                'percentage' => $totalReviews > 0 ? ($count / $totalReviews) * 100 : 0,
            ];
        }

        return [
            'total_reviews' => $totalReviews,
            'average_rating' => round($averageRating, 2),
            'distribution' => $distribution,
            'verified_count' => $reviews->clone()->verified()->count(),
            'with_photos_count' => $reviews->clone()->withPhotos()->count(),
            'with_merchant_response' => $reviews->clone()->whereNotNull('merchant_response')->count(),
        ];
    }
}

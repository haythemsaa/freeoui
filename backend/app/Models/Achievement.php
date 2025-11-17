<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Achievement extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'icon',
        'tier',
        'points_reward',
        'criteria',
        'is_active',
    ];

    protected $casts = [
        'criteria' => 'array',
        'is_active' => 'boolean',
    ];

    public function userAchievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByTier($query, string $tier)
    {
        return $query->where('tier', $tier);
    }

    public function checkCriteria(User $user): bool
    {
        if (!$this->criteria) {
            return false;
        }

        foreach ($this->criteria as $criterion => $value) {
            switch ($criterion) {
                case 'min_scans':
                    if ($user->qr_scans_count < $value) {
                        return false;
                    }
                    break;

                case 'min_points':
                    if ($user->loyalty_points_lifetime < $value) {
                        return false;
                    }
                    break;

                case 'min_reviews':
                    if ($user->reviews()->count() < $value) {
                        return false;
                    }
                    break;

                case 'min_referrals':
                    if ($user->successful_referrals < $value) {
                        return false;
                    }
                    break;

                case 'min_bookings':
                    if ($user->bookings()->whereIn('status', ['completed'])->count() < $value) {
                        return false;
                    }
                    break;

                case 'loyalty_tier':
                    if ($user->loyalty_tier !== $value) {
                        return false;
                    }
                    break;

                default:
                    // Unknown criterion, skip
                    break;
            }
        }

        return true;
    }

    public function calculateProgress(User $user): float
    {
        if (!$this->criteria) {
            return 0;
        }

        $totalCriteria = count($this->criteria);
        $metCriteria = 0;

        foreach ($this->criteria as $criterion => $value) {
            $userValue = 0;

            switch ($criterion) {
                case 'min_scans':
                    $userValue = $user->qr_scans_count;
                    break;
                case 'min_points':
                    $userValue = $user->loyalty_points_lifetime;
                    break;
                case 'min_reviews':
                    $userValue = $user->reviews()->count();
                    break;
                case 'min_referrals':
                    $userValue = $user->successful_referrals;
                    break;
                case 'min_bookings':
                    $userValue = $user->bookings()->whereIn('status', ['completed'])->count();
                    break;
            }

            if ($userValue >= $value) {
                $metCriteria++;
            }
        }

        return ($metCriteria / $totalCriteria) * 100;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StickerCollection extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'sticker_type',
        'sticker_tier',
        'visit_count',
        'coin_multiplier',
        'unlocked_at',
    ];

    protected $casts = [
        'visit_count' => 'integer',
        'coin_multiplier' => 'decimal:2',
        'unlocked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function incrementVisit(): void
    {
        $this->increment('visit_count');
        $this->checkTierUpgrade();
    }

    private function checkTierUpgrade(): void
    {
        $tierThresholds = [
            'bronze' => 1,
            'silver' => 5,
            'gold' => 15,
            'diamond' => 50,
        ];

        foreach ($tierThresholds as $tier => $threshold) {
            if ($this->visit_count >= $threshold) {
                $this->sticker_tier = $tier;
                $this->coin_multiplier = $this->calculateMultiplier($tier);
            }
        }

        $this->save();
    }

    private function calculateMultiplier(string $tier): float
    {
        return match($tier) {
            'bronze' => 1.0,
            'silver' => 1.2,
            'gold' => 1.5,
            'diamond' => 2.0,
            default => 1.0,
        };
    }
}

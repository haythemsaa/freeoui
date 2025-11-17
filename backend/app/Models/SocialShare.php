<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SocialShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shareable_type',
        'shareable_id',
        'platform',
        'share_type',
        'share_url',
        'share_text',
        'share_image_url',
        'click_count',
        'conversion_count',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shareable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    public function incrementClicks(): void
    {
        $this->increment('click_count');
    }

    public function incrementConversions(): void
    {
        $this->increment('conversion_count');
    }

    public function getConversionRate(): float
    {
        if ($this->click_count === 0) {
            return 0;
        }

        return ($this->conversion_count / $this->click_count) * 100;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Story extends Model
{
    protected $fillable = [
        'user_id',
        'merchant_id',
        'type',
        'media_url',
        'thumbnail_url',
        'caption',
        'duration',
        'background_color',
        'views_count',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'duration' => 'integer',
        'views_count' => 'integer',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(StoryView::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at < now();
    }
}

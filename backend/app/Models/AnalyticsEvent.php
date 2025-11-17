<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsEvent extends Model
{
    use HasFactory;

    const UPDATED_AT = null; // No updated_at

    protected $fillable = [
        'user_id',
        'event_name',
        'event_category',
        'properties',
        'screen_name',
        'platform',
        'app_version',
        'device_model',
        'os_version',
        'location',
        'session_id',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByEvent($query, string $eventName)
    {
        return $query->where('event_name', $eventName);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('event_category', $category);
    }

    public function scopeInSession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }
}

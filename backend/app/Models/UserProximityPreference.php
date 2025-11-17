<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProximityPreference extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'proximity_alerts_enabled',
        'proximity_radius_meters',
        'interested_category_ids',
        'quiet_hours_start',
        'quiet_hours_end',
        'max_daily_notifications',
        'min_notification_interval_minutes',
        'notify_only_when_moving',
        'notify_for_featured_only',
        'minimum_discount_percentage',
    ];

    protected $casts = [
        'proximity_alerts_enabled' => 'boolean',
        'proximity_radius_meters' => 'integer',
        'interested_category_ids' => 'array',
        'max_daily_notifications' => 'integer',
        'min_notification_interval_minutes' => 'integer',
        'notify_only_when_moving' => 'boolean',
        'notify_for_featured_only' => 'boolean',
        'minimum_discount_percentage' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if currently in quiet hours
     */
    public function isInQuietHours(): bool
    {
        if (!$this->quiet_hours_start || !$this->quiet_hours_end) {
            return false;
        }

        $currentTime = now()->format('H:i');
        return $currentTime >= $this->quiet_hours_start || $currentTime <= $this->quiet_hours_end;
    }
}

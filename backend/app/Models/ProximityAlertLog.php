<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProximityAlertLog extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'advantage_id',
        'merchant_id',
        'user_latitude',
        'user_longitude',
        'distance_meters',
        'notification_sent',
        'notification_sent_at',
        'notification_opened',
        'notification_opened_at',
        'notification_fcm_id',
        'advantage_viewed',
        'advantage_viewed_at',
        'advantage_saved',
        'qr_code_generated',
        'advantage_used',
        'user_speed_mps',
        'time_of_day',
        'day_of_week',
        'relevance_score',
    ];

    protected $casts = [
        'user_latitude' => 'decimal:8',
        'user_longitude' => 'decimal:8',
        'distance_meters' => 'decimal:2',
        'notification_sent' => 'boolean',
        'notification_sent_at' => 'datetime',
        'notification_opened' => 'boolean',
        'notification_opened_at' => 'datetime',
        'advantage_viewed' => 'boolean',
        'advantage_viewed_at' => 'datetime',
        'advantage_saved' => 'boolean',
        'qr_code_generated' => 'boolean',
        'advantage_used' => 'boolean',
        'user_speed_mps' => 'decimal:2',
        'day_of_week' => 'integer',
        'relevance_score' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function advantage(): BelongsTo
    {
        return $this->belongsTo(Advantage::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * Mark notification as opened
     */
    public function markAsOpened(): void
    {
        if (!$this->notification_opened) {
            $this->update([
                'notification_opened' => true,
                'notification_opened_at' => now(),
            ]);

            $this->advantage->incrementProximityAlertsOpened();
        }
    }

    /**
     * Mark advantage as viewed
     */
    public function markAsViewed(): void
    {
        if (!$this->advantage_viewed) {
            $this->update([
                'advantage_viewed' => true,
                'advantage_viewed_at' => now(),
            ]);
        }
    }

    /**
     * Scope to get sent alerts
     */
    public function scopeSent($query)
    {
        return $query->where('notification_sent', true);
    }

    /**
     * Scope to get opened alerts
     */
    public function scopeOpened($query)
    {
        return $query->where('notification_opened', true);
    }

    /**
     * Scope to get converted alerts (used)
     */
    public function scopeConverted($query)
    {
        return $query->where('advantage_used', true);
    }
}

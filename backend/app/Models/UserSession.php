<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'user_id',
        'started_at',
        'ended_at',
        'duration_seconds',
        'platform',
        'app_version',
        'device_model',
        'os_version',
        'screens_viewed',
        'actions_performed',
        'start_location',
        'end_location',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(AnalyticsEvent::class, 'session_id', 'session_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('ended_at');
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('ended_at');
    }

    public function endSession(): void
    {
        $duration = now()->diffInSeconds($this->started_at);

        $this->update([
            'ended_at' => now(),
            'duration_seconds' => $duration,
        ]);

        // Update user stats
        if ($this->user) {
            $this->user->increment('total_sessions');
            $this->user->increment('total_session_duration_minutes', $duration / 60);
            $this->user->update([
                'last_active_at' => now(),
                'avg_session_duration_minutes' => $this->user->total_session_duration_minutes / $this->user->total_sessions,
            ]);
        }
    }

    public function incrementScreensViewed(): void
    {
        $this->increment('screens_viewed');

        if ($this->user) {
            $this->user->increment('screens_viewed_total');
        }
    }

    public function incrementActions(): void
    {
        $this->increment('actions_performed');
    }
}

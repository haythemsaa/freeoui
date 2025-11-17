<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Challenge extends Model
{
    protected $fillable = [
        'name',
        'description',
        'challenge_type',
        'criteria',
        'reward_points',
        'badge_icon',
        'starts_at',
        'ends_at',
        'is_active',
        'is_recurring',
        'recurrence_type',
    ];

    protected $casts = [
        'criteria' => 'array',
        'reward_points' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
        'is_recurring' => 'boolean',
    ];

    public function participations(): HasMany
    {
        return $this->hasMany(ChallengeParticipation::class);
    }

    public function isOngoing(): bool
    {
        return $this->is_active 
            && $this->starts_at <= now() 
            && $this->ends_at >= now();
    }

    public function isExpired(): bool
    {
        return $this->ends_at < now();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now());
    }
}

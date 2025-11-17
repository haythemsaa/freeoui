<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAchievement extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'achievement_id',
        'progress',
        'unlocked_at',
    ];

    protected $casts = [
        'progress' => 'float',
        'unlocked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class);
    }

    public function scopeUnlocked($query)
    {
        return $query->whereNotNull('unlocked_at');
    }

    public function scopeInProgress($query)
    {
        return $query->whereNull('unlocked_at');
    }

    public function isUnlocked(): bool
    {
        return $this->unlocked_at !== null;
    }

    public function unlock(): void
    {
        $this->update([
            'progress' => 100,
            'unlocked_at' => now(),
        ]);
    }

    public function updateProgress(float $progress): void
    {
        $this->update(['progress' => min(100, $progress)]);

        if ($progress >= 100 && !$this->isUnlocked()) {
            $this->unlock();
        }
    }
}

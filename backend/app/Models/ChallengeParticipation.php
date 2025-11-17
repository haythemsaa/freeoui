<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChallengeParticipation extends Model
{
    protected $fillable = [
        'user_id',
        'challenge_id',
        'progress',
        'progress_percentage',
        'status',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'progress' => 'array',
        'progress_percentage' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function updateProgress(array $newProgress): void
    {
        $this->progress = $newProgress;
        $this->progress_percentage = $this->calculatePercentage($newProgress);
        
        if ($this->progress_percentage >= 100 && $this->status !== 'completed') {
            $this->status = 'completed';
            $this->completed_at = now();
        }
        
        $this->save();
    }

    private function calculatePercentage(array $progress): int
    {
        if (!isset($progress['current']) || !isset($progress['target'])) {
            return 0;
        }

        return min(100, (int) (($progress['current'] / $progress['target']) * 100));
    }
}

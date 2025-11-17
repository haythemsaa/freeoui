<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Referral extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrer_id',
        'referee_id',
        'referral_code',
        'status',
        'referrer_reward_points',
        'referee_reward_points',
        'referrer_reward_amount',
        'referee_reward_amount',
        'completed_at',
        'rewarded_at',
    ];

    protected $casts = [
        'referrer_reward_amount' => 'decimal:3',
        'referee_reward_amount' => 'decimal:3',
        'completed_at' => 'datetime',
        'rewarded_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($referral) {
            if (!$referral->referral_code) {
                $referral->referral_code = strtoupper(Str::random(8));
            }
        });
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referee_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeRewarded($query)
    {
        return $query->where('status', 'rewarded');
    }

    public function scopeForReferrer($query, int $userId)
    {
        return $query->where('referrer_id', $userId);
    }

    public function scopeForReferee($query, int $userId)
    {
        return $query->where('referee_id', $userId);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isRewarded(): bool
    {
        return $this->status === 'rewarded';
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function markAsRewarded(): void
    {
        $this->update([
            'status' => 'rewarded',
            'rewarded_at' => now(),
        ]);
    }
}

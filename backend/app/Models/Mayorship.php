<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mayorship extends Model
{
    protected $fillable = [
        'user_id',
        'merchant_id',
        'checkin_count',
        'claimed_at',
        'last_checkin_at',
        'is_active',
    ];

    protected $casts = [
        'checkin_count' => 'integer',
        'claimed_at' => 'datetime',
        'last_checkin_at' => 'datetime',
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

    public function isExpired(): bool
    {
        return $this->last_checkin_at < now()->subDays(30);
    }

    public function incrementCheckin(): void
    {
        $this->increment('checkin_count');
        $this->update(['last_checkin_at' => now()]);
    }
}

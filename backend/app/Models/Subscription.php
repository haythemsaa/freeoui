<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'plan_type',
        'price',
        'discount_percentage',
        'starts_at',
        'ends_at',
        'renews_at',
        'status',
        'payment_id',
    ];

    protected $casts = [
        'price' => 'decimal:3',
        'discount_percentage' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'renews_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->ends_at > now();
    }

    public function applyDiscount(float $amount): float
    {
        if (!$this->isActive()) {
            return $amount;
        }
        
        return $amount * (1 - $this->discount_percentage / 100);
    }

    public function daysRemaining(): int
    {
        if (!$this->isActive()) {
            return 0;
        }
        
        return now()->diffInDays($this->ends_at);
    }
}

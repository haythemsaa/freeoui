<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GiftCard extends Model
{
    protected $fillable = [
        'code',
        'buyer_id',
        'redeemed_by_id',
        'recipient_email',
        'recipient_phone',
        'amount',
        'balance',
        'message',
        'design_template',
        'status',
        'redeemed_at',
        'expires_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'redeemed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function redeemedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'redeemed_by_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->balance > 0 && $this->expires_at > now();
    }

    public function isExpired(): bool
    {
        return $this->expires_at < now();
    }
}

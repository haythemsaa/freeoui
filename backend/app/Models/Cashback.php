<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cashback extends Model
{
    protected $fillable = [
        'user_id',
        'transaction_id',
        'merchant_id',
        'amount',
        'rate',
        'status',
        'eligible_at',
        'processed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'rate' => 'decimal:2',
        'eligible_at' => 'datetime',
        'processed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function isEligible(): bool
    {
        return $this->status === 'pending' && $this->eligible_at <= now();
    }
}

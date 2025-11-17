<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'merchant_id',
        'advantage_id',
        'transaction_id',
        'rating',
        'title',
        'comment',
        'photos',
        'merchant_response',
        'merchant_response_at',
        'merchant_response_by',
        'status',
        'moderated_by',
        'moderated_at',
        'rejection_reason',
        'helpful_count',
        'not_helpful_count',
    ];

    protected $casts = [
        'rating' => 'integer',
        'photos' => 'array',
        'merchant_response_at' => 'datetime',
        'moderated_at' => 'datetime',
        'helpful_count' => 'integer',
        'not_helpful_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function advantage(): BelongsTo
    {
        return $this->belongsTo(Advantage::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}

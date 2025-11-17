<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'merchant_id',
        'advantage_id',
        'qr_code_id',
        'original_amount',
        'discount_amount',
        'final_amount',
        'currency',
        'transaction_date',
        'validated_by',
        'latitude',
        'longitude',
        'points_earned',
        'notes',
        'merchant_notes',
    ];

    protected $casts = [
        'original_amount' => 'decimal:3',
        'discount_amount' => 'decimal:3',
        'final_amount' => 'decimal:3',
        'transaction_date' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'points_earned' => 'integer',
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

    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QrCode::class);
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}

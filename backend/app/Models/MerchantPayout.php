<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MerchantPayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'payout_number',
        'merchant_id',
        'amount',
        'commission_deducted',
        'net_amount',
        'method',
        'status',
        'bank_details',
        'transaction_reference',
        'processed_at',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:3',
        'commission_deducted' => 'decimal:3',
        'net_amount' => 'decimal:3',
        'bank_details' => 'array',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($payout) {
            if (!$payout->payout_number) {
                $payout->payout_number = 'PAYOUT-' . strtoupper(Str::random(10));
            }
        });
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'processed_at' => now(),
        ]);
    }

    public function markAsCompleted(string $transactionReference): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'transaction_reference' => $transactionReference,
        ]);
    }

    public function markAsFailed(string $notes): void
    {
        $this->update([
            'status' => 'failed',
            'notes' => $notes,
        ]);
    }
}

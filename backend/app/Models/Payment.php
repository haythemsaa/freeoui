<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_number',
        'user_id',
        'merchant_id',
        'booking_id',
        'payment_type',
        'payment_provider',
        'amount',
        'fee',
        'merchant_amount',
        'platform_commission',
        'currency',
        'status',
        'provider_transaction_id',
        'provider_response',
        'description',
        'metadata',
        'completed_at',
        'failed_at',
        'refunded_at',
        'failure_reason',
        'refund_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:3',
        'fee' => 'decimal:3',
        'merchant_amount' => 'decimal:3',
        'platform_commission' => 'decimal:3',
        'provider_response' => 'array',
        'metadata' => 'array',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($payment) {
            if (!$payment->payment_number) {
                $payment->payment_number = 'PAY-' . strtoupper(Str::random(10));
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
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

    public function scopeRefunded($query)
    {
        return $query->where('status', 'refunded');
    }

    public function scopeByProvider($query, string $provider)
    {
        return $query->where('payment_provider', $provider);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    public function markAsCompleted(string $providerTransactionId = null): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'provider_transaction_id' => $providerTransactionId ?? $this->provider_transaction_id,
        ]);
    }

    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'failure_reason' => $reason,
        ]);
    }

    public function markAsRefunded(string $reason = null): void
    {
        $this->update([
            'status' => 'refunded',
            'refunded_at' => now(),
            'refund_reason' => $reason,
        ]);
    }
}

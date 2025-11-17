<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commission extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'payment_id',
        'booking_id',
        'commission_type',
        'transaction_amount',
        'commission_rate',
        'commission_amount',
        'status',
        'approved_at',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'transaction_amount' => 'decimal:3',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:3',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeDisputed($query)
    {
        return $query->where('status', 'disputed');
    }

    public function approve(): void
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }
}

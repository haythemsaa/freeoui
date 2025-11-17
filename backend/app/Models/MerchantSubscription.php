<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantSubscription extends Model
{
    use HasUuids;

    protected $fillable = [
        'merchant_id',
        'plan_id',
        'start_date',
        'end_date',
        'billing_cycle',
        'amount',
        'currency',
        'payment_status',
        'payment_method',
        'payment_reference',
        'payment_date',
        'auto_renew',
        'renewal_reminder_sent',
        'status',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'amount' => 'decimal:3',
        'payment_date' => 'datetime',
        'auto_renew' => 'boolean',
        'renewal_reminder_sent' => 'boolean',
        'cancelled_at' => 'datetime',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->end_date >= now();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('end_date', '>=', now());
    }
}

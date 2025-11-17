<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Boost extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'boostable_type',
        'boostable_id',
        'boost_type',
        'daily_budget',
        'total_budget',
        'spent_amount',
        'cost_per_view',
        'cost_per_click',
        'impressions',
        'clicks',
        'conversions',
        'conversion_rate',
        'target_audience',
        'target_locations',
        'status',
        'start_date',
        'end_date',
        'priority',
        'approved_at',
        'paused_at',
        'completed_at',
    ];

    protected $casts = [
        'daily_budget' => 'decimal:3',
        'total_budget' => 'decimal:3',
        'spent_amount' => 'decimal:3',
        'cost_per_view' => 'decimal:3',
        'cost_per_click' => 'decimal:3',
        'conversion_rate' => 'decimal:2',
        'target_audience' => 'array',
        'target_locations' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'paused_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function boostable(): MorphTo
    {
        return $this->morphTo();
    }

    public function events(): HasMany
    {
        return $this->hasMany(BoostEvent::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->where(function ($q) {
                $q->whereNull('total_budget')
                  ->orWhereRaw('spent_amount < total_budget');
            });
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaused($query)
    {
        return $query->where('status', 'paused');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && $this->start_date->isPast()
            && $this->end_date->isFuture()
            && ($this->total_budget === null || $this->spent_amount < $this->total_budget);
    }

    public function canCharge(float $amount): bool
    {
        if ($this->total_budget === null) {
            return true;
        }

        return ($this->spent_amount + $amount) <= $this->total_budget;
    }

    public function recordImpression(?User $user = null): void
    {
        if (!$this->isActive() || !$this->canCharge($this->cost_per_view)) {
            return;
        }

        $this->increment('impressions');
        $this->increment('spent_amount', $this->cost_per_view);

        BoostEvent::create([
            'boost_id' => $this->id,
            'user_id' => $user?->id,
            'event_type' => 'impression',
            'charge_amount' => $this->cost_per_view,
        ]);

        $this->checkCompletion();
    }

    public function recordClick(?User $user = null): void
    {
        if (!$this->isActive() || !$this->canCharge($this->cost_per_click)) {
            return;
        }

        $this->increment('clicks');
        $this->increment('spent_amount', $this->cost_per_click);

        BoostEvent::create([
            'boost_id' => $this->id,
            'user_id' => $user?->id,
            'event_type' => 'click',
            'charge_amount' => $this->cost_per_click,
        ]);

        $this->updateConversionRate();
        $this->checkCompletion();
    }

    public function recordConversion(?User $user = null): void
    {
        $this->increment('conversions');

        BoostEvent::create([
            'boost_id' => $this->id,
            'user_id' => $user?->id,
            'event_type' => 'conversion',
            'charge_amount' => 0,
        ]);

        $this->updateConversionRate();
    }

    protected function updateConversionRate(): void
    {
        if ($this->clicks > 0) {
            $rate = ($this->conversions / $this->clicks) * 100;
            $this->update(['conversion_rate' => round($rate, 2)]);
        }
    }

    protected function checkCompletion(): void
    {
        if ($this->total_budget && $this->spent_amount >= $this->total_budget) {
            $this->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }
    }

    public function pause(): void
    {
        $this->update([
            'status' => 'paused',
            'paused_at' => now(),
        ]);
    }

    public function resume(): void
    {
        $this->update([
            'status' => 'active',
            'paused_at' => null,
        ]);
    }

    public function approve(): void
    {
        $this->update([
            'status' => 'active',
            'approved_at' => now(),
        ]);
    }
}

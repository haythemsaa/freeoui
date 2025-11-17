<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'name',
        'description',
        'campaign_type',
        'rules',
        'budget',
        'spent',
        'max_participants',
        'current_participants',
        'status',
        'start_date',
        'end_date',
        'views_count',
        'participations_count',
        'conversions_count',
    ];

    protected $casts = [
        'rules' => 'array',
        'budget' => 'decimal:3',
        'spent' => 'decimal:3',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled')
            ->where('start_date', '>', now());
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && $this->start_date->isPast()
            && $this->end_date->isFuture();
    }

    public function canParticipate(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        if ($this->max_participants && $this->current_participants >= $this->max_participants) {
            return false;
        }

        if ($this->budget && $this->spent >= $this->budget) {
            return false;
        }

        return true;
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    public function incrementParticipations(): void
    {
        $this->increment('participations_count');
        $this->increment('current_participants');
    }

    public function incrementConversions(): void
    {
        $this->increment('conversions_count');
    }
}

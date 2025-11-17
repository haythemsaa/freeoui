<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoostEvent extends Model
{
    use HasFactory;

    const UPDATED_AT = null; // No updated_at timestamp

    protected $fillable = [
        'boost_id',
        'user_id',
        'event_type',
        'charge_amount',
        'user_agent',
        'ip_address',
        'location',
        'metadata',
    ];

    protected $casts = [
        'charge_amount' => 'decimal:3',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function boost(): BelongsTo
    {
        return $this->belongsTo(Boost::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeImpressions($query)
    {
        return $query->where('event_type', 'impression');
    }

    public function scopeClicks($query)
    {
        return $query->where('event_type', 'click');
    }

    public function scopeConversions($query)
    {
        return $query->where('event_type', 'conversion');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingBlackout extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'blackout_date',
        'start_time',
        'end_time',
        'reason',
        'is_recurring',
    ];

    protected $casts = [
        'blackout_date' => 'date',
        'is_recurring' => 'boolean',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('blackout_date', $date);
    }

    public function scopeActive($query)
    {
        return $query->where('blackout_date', '>=', now()->toDateString());
    }

    public function isActiveForDateTime(string $date, string $time): bool
    {
        if ($this->blackout_date->toDateString() !== $date) {
            return false;
        }

        // If no specific time range, the whole day is blacked out
        if (!$this->start_time && !$this->end_time) {
            return true;
        }

        // Check if the time falls within the blackout range
        if ($this->start_time && $this->end_time) {
            return $time >= $this->start_time && $time <= $this->end_time;
        }

        return false;
    }
}

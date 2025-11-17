<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'day_of_week',
        'start_time',
        'end_time',
        'max_capacity',
        'duration_minutes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForDay($query, string $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    public function getAvailableSlots(string $date): array
    {
        $slots = [];
        $start = \Carbon\Carbon::parse($this->start_time);
        $end = \Carbon\Carbon::parse($this->end_time);

        while ($start->lt($end)) {
            $slotTime = $start->format('H:i:s');

            // Check if this time slot is available
            $bookedCount = Booking::where('merchant_id', $this->merchant_id)
                ->where('booking_date', $date)
                ->where('booking_time', $slotTime)
                ->whereIn('status', ['pending', 'confirmed'])
                ->sum('party_size');

            if ($bookedCount < $this->max_capacity) {
                $slots[] = [
                    'time' => $slotTime,
                    'available_capacity' => $this->max_capacity - $bookedCount,
                ];
            }

            $start->addMinutes($this->duration_minutes);
        }

        return $slots;
    }
}

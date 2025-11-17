<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLocation extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'latitude',
        'longitude',
        'accuracy_meters',
        'altitude',
        'speed_mps',
        'heading_degrees',
        'source',
        'recorded_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'accuracy_meters' => 'decimal:2',
        'altitude' => 'decimal:2',
        'speed_mps' => 'decimal:2',
        'heading_degrees' => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate distance to a point in meters
     */
    public function distanceTo(float $latitude, float $longitude): float
    {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($latitude - $this->latitude);
        $dLon = deg2rad($longitude - $this->longitude);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($this->latitude)) * cos(deg2rad($latitude)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Check if user is moving (speed > 0.5 m/s)
     */
    public function isMoving(): bool
    {
        return $this->speed_mps && $this->speed_mps > 0.5;
    }

    /**
     * Scope to get recent locations
     */
    public function scopeRecent($query, int $minutes = 30)
    {
        return $query->where('recorded_at', '>=', now()->subMinutes($minutes));
    }
}

<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait HasGeolocation
{
    /**
     * Scope to find records within radius of a point
     */
    public function scopeWithinRadius(
        Builder $query,
        float $latitude,
        float $longitude,
        float $radiusMeters,
        string $latColumn = 'latitude',
        string $lonColumn = 'longitude'
    ): Builder {
        return $query->whereRaw(
            "ST_DWithin(
                location::geography,
                ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography,
                ?
            )",
            [$longitude, $latitude, $radiusMeters]
        );
    }

    /**
     * Scope to order by distance from a point
     */
    public function scopeOrderByDistance(
        Builder $query,
        float $latitude,
        float $longitude,
        string $direction = 'asc'
    ): Builder {
        return $query->orderByRaw(
            "ST_Distance(
                location::geography,
                ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography
            ) {$direction}",
            [$longitude, $latitude]
        );
    }

    /**
     * Get distance to a point in meters
     */
    public function getDistanceTo(float $latitude, float $longitude): float
    {
        $result = DB::selectOne(
            "SELECT ST_Distance(
                location::geography,
                ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography
            ) as distance",
            [$longitude, $latitude]
        );

        return (float) $result->distance;
    }

    /**
     * Check if within radius of a point
     */
    public function isWithinRadius(float $latitude, float $longitude, float $radiusMeters): bool
    {
        return $this->getDistanceTo($latitude, $longitude) <= $radiusMeters;
    }

    /**
     * Scope to add distance column
     */
    public function scopeWithDistance(
        Builder $query,
        float $latitude,
        float $longitude,
        string $alias = 'distance'
    ): Builder {
        return $query->selectRaw(
            "*, ST_Distance(
                location::geography,
                ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography
            ) as {$alias}",
            [$longitude, $latitude]
        );
    }
}

<?php

namespace App\Helpers;

class DistanceHelper
{
    /**
     * Calculate distance between two coordinates using Haversine formula
     * Returns distance in meters
     */
    public static function calculate(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // meters

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos($latFrom) * cos($latTo) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Format distance for human-readable display
     */
    public static function format(float $meters): string
    {
        if ($meters < 1000) {
            return round($meters) . ' m';
        }

        return round($meters / 1000, 1) . ' km';
    }

    /**
     * Check if a point is within radius of another point
     */
    public static function isWithinRadius(float $lat1, float $lon1, float $lat2, float $lon2, float $radiusMeters): bool
    {
        return self::calculate($lat1, $lon1, $lat2, $lon2) <= $radiusMeters;
    }

    /**
     * Get bounding box for a given point and radius
     * Returns [minLat, maxLat, minLon, maxLon]
     */
    public static function getBoundingBox(float $lat, float $lon, float $radiusMeters): array
    {
        $earthRadius = 6371000; // meters

        $latRadian = deg2rad($lat);
        $degLat = rad2deg($radiusMeters / $earthRadius);
        $degLon = rad2deg($radiusMeters / $earthRadius / cos($latRadian));

        return [
            'min_lat' => $lat - $degLat,
            'max_lat' => $lat + $degLat,
            'min_lon' => $lon - $degLon,
            'max_lon' => $lon + $degLon,
        ];
    }
}

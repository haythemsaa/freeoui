<?php

namespace App\Helpers;

use Carbon\Carbon;

class DateHelper
{
    /**
     * Format date for French locale
     */
    public static function formatFrench(string|Carbon $date, string $format = 'd/m/Y'): string
    {
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);
        return $carbon->locale('fr')->isoFormat($format);
    }

    /**
     * Format date for Arabic locale
     */
    public static function formatArabic(string|Carbon $date, string $format = 'd/m/Y'): string
    {
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);
        return $carbon->locale('ar')->isoFormat($format);
    }

    /**
     * Format date with time for French locale
     */
    public static function formatDateTimeFrench(string|Carbon $date): string
    {
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);
        return $carbon->locale('fr')->isoFormat('D MMMM YYYY [à] HH:mm');
    }

    /**
     * Get human-readable time difference
     */
    public static function diffForHumans(string|Carbon $date, string $locale = 'fr'): string
    {
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);
        return $carbon->locale($locale)->diffForHumans();
    }

    /**
     * Check if date is within range
     */
    public static function isWithinRange(string|Carbon $date, string|Carbon $start, string|Carbon $end): bool
    {
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);
        $startCarbon = $start instanceof Carbon ? $start : Carbon::parse($start);
        $endCarbon = $end instanceof Carbon ? $end : Carbon::parse($end);

        return $carbon->between($startCarbon, $endCarbon);
    }

    /**
     * Get day of week number (0 = Sunday, 6 = Saturday)
     */
    public static function getDayOfWeek(string|Carbon $date): int
    {
        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);
        return $carbon->dayOfWeek;
    }

    /**
     * Check if current time is within business hours
     */
    public static function isBusinessHours(string|Carbon $datetime = null, string $openTime = '09:00', string $closeTime = '18:00'): bool
    {
        $carbon = $datetime ? ($datetime instanceof Carbon ? $datetime : Carbon::parse($datetime)) : Carbon::now();
        $open = Carbon::parse($openTime);
        $close = Carbon::parse($closeTime);

        return $carbon->between($open, $close);
    }
}

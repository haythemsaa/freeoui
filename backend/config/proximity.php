<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Proximity Alert Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for the proximity alert system.
    |
    */

    'max_alerts_per_day' => env('PROXIMITY_MAX_ALERTS_PER_DAY', 5),

    'min_interval_minutes' => env('PROXIMITY_MIN_INTERVAL_MINUTES', 30),

    'default_radius_meters' => env('PROXIMITY_DEFAULT_RADIUS_METERS', 1000),

    'max_radius_meters' => 5000,

    'min_radius_meters' => 500,

    /*
    |--------------------------------------------------------------------------
    | Relevance Score Weights
    |--------------------------------------------------------------------------
    |
    | Weights for calculating the relevance score of an advantage.
    |
    */

    'score_weights' => [
        'proximity' => 30,      // Max 30 points for proximity
        'discount' => 50,       // Max 50 points for discount value
        'popularity' => 20,     // Max 20 points for popularity
        'quality' => 25,        // Max 25 points for merchant rating (5 * 5)
        'urgency' => 15,        // Bonus 15 points for expiring soon
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Scheduling
    |--------------------------------------------------------------------------
    |
    | Configuration for when to send alerts.
    |
    */

    'allowed_hours' => [
        'start' => 8,   // 8 AM
        'end' => 22,    // 10 PM
    ],

    'excluded_days' => [], // Empty = all days allowed

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Queue settings for proximity alert jobs.
    |
    */

    'queue' => [
        'name' => 'proximity-alerts',
        'retry_after' => 90,
        'max_tries' => 3,
    ],

];

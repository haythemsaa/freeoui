<?php

return [

    /*
    |--------------------------------------------------------------------------
    | QR Code Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for QR code generation and validation.
    |
    */

    'secret' => env('QR_SECRET', 'your-qr-secret-key'),

    'validity_hours' => env('QR_VALIDITY_HOURS', 2),

    'algorithm' => 'sha256',

    'code_length' => 12,

    'code_prefix' => 'FO',

    /*
    |--------------------------------------------------------------------------
    | QR Code Validation Rules
    |--------------------------------------------------------------------------
    */

    'validation' => [
        'check_merchant' => true,
        'check_signature' => true,
        'check_expiration' => true,
        'check_usage' => true,
        'allow_partial_use' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Usage Limits
    |--------------------------------------------------------------------------
    */

    'limits' => [
        'max_per_user_per_day' => 10,
        'max_per_advantage_per_user' => 1,
        'cooldown_minutes' => 5,
    ],

];

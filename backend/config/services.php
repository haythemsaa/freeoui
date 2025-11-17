<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Firebase, payment gateways, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'fcm' => [
        'server_key' => env('FCM_SERVER_KEY'),
    ],

    'd17' => [
        'api_url' => env('D17_API_URL', 'https://api.d17.tn'),
        'api_key' => env('D17_API_KEY'),
        'api_secret' => env('D17_API_SECRET'),
    ],

    'flouci' => [
        'api_url' => env('FLOUCI_API_URL', 'https://developers.flouci.com/api'),
        'app_token' => env('FLOUCI_APP_TOKEN'),
        'app_secret' => env('FLOUCI_APP_SECRET'),
    ],

    'paymee' => [
        'api_url' => env('PAYMEE_API_URL', 'https://api.paymee.tn'),
        'api_key' => env('PAYMEE_API_KEY'),
        'vendor_id' => env('PAYMEE_VENDOR_ID'),
    ],

    'aws' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'eu-west-1'),
        'bucket' => env('AWS_BUCKET'),
    ],

    'sentry' => [
        'dsn' => env('SENTRY_LARAVEL_DSN'),
        'traces_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE', 0.2),
    ],

    'newrelic' => [
        'license_key' => env('NEW_RELIC_LICENSE_KEY'),
        'app_name' => env('NEW_RELIC_APP_NAME', 'FreeOui'),
    ],

];

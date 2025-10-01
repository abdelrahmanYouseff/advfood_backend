<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Noon Payments Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration options for Noon Payments integration.
    | Make sure to set the appropriate values in your .env file.
    |
    */

    'api_key' => env('NOON_API_KEY'),
    'api_url' => env('NOON_API_URL', 'https://api-test.sa.noonpayments.com'),
    'application_id' => env('NOON_APPLICATION_ID'),
    'business_id' => env('NOON_BUSINESS_ID'),
    'success_url' => env('NOON_SUCCESS_URL'),
    'failure_url' => env('NOON_FAILURE_URL'),

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'currency' => 'SAR',
        'timeout' => 30,
        'description' => 'Payment order',
        'category' => 'pay',
        'channel' => 'web',
    ],

    /*
    |--------------------------------------------------------------------------
    | Environment Settings
    |--------------------------------------------------------------------------
    */

    'environment' => env('NOON_ENVIRONMENT', 'test'), // test or production
    'debug' => env('NOON_DEBUG', false),
];

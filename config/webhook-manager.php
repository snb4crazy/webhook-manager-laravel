<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Package Switch
    |--------------------------------------------------------------------------
    */
    'enabled' => env('WEBHOOK_MANAGER_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Signing
    |--------------------------------------------------------------------------
    */
    'default_secret' => env('WEBHOOK_MANAGER_SECRET'),
    'signature_header' => env('WEBHOOK_MANAGER_SIGNATURE_HEADER', 'X-Webhook-Signature'),
    'timestamp_tolerance' => (int) env('WEBHOOK_MANAGER_TIMESTAMP_TOLERANCE', 300),

    /*
    |--------------------------------------------------------------------------
    | Delivery
    |--------------------------------------------------------------------------
    */
    'queue' => env('WEBHOOK_MANAGER_QUEUE', true),
    'queue_connection' => env('WEBHOOK_MANAGER_QUEUE_CONNECTION'),
    'queue_name' => env('WEBHOOK_MANAGER_QUEUE_NAME'),
    'max_attempts' => (int) env('WEBHOOK_MANAGER_MAX_ATTEMPTS', 3),
    'retry_backoff' => [10, 30, 120],

    'http' => [
        'connect_timeout' => (int) env('WEBHOOK_MANAGER_CONNECT_TIMEOUT', 5),
        'timeout' => (int) env('WEBHOOK_MANAGER_TIMEOUT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */
    'log_channel' => env('WEBHOOK_MANAGER_LOG_CHANNEL'),
    'store_response_body' => env('WEBHOOK_MANAGER_STORE_RESPONSE_BODY', false),
];


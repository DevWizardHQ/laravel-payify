<?php

return [
    'default' => env('PAYIFY_DEFAULT', 'fake'),
    'mode' => env('PAYIFY_MODE', 'sandbox'),
    'default_currency' => env('PAYIFY_CURRENCY', 'BDT'),
    'throw_exceptions' => env('PAYIFY_THROW'),
    'log_channel' => env('PAYIFY_LOG'),
    'table' => 'payify_transactions',

    'idempotency' => [
        'enabled' => true,
    ],

    'http' => [
        'timeout' => 30,
        'connect_timeout' => 10,
        'retries' => 2,
        'retry_delay' => 1000,
        'verify' => true,
        'user_agent' => 'Payify/1.0',
        'log_requests' => env('PAYIFY_LOG_HTTP', false),
        'mask_keys' => [
            'secret', 'password', 'passwd', 'private_key',
            'api_key', 'access_token', 'authorization', 'signature',
        ],
    ],

    'routes' => [
        'enabled' => true,
        'prefix' => 'payify',
        'middleware' => ['api'],
        'domain' => null,
    ],

    'callback' => [
        'redirect_url' => env('PAYIFY_CALLBACK_REDIRECT'),
    ],

    'webhooks' => [
        'queue' => env('PAYIFY_WEBHOOK_QUEUE'),
        'tries' => 3,
        'backoff' => 10,
    ],

    'cleanup' => [
        'pending_ttl_days' => 7,
        'failed_ttl_days' => 30,
    ],

    'providers' => [
        // Register drivers here. Phase 1 ships no built-ins.
    ],
];

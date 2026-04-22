<?php

return [
    'default' => env('PAYIFY_DEFAULT'),
    'mode' => env('PAYIFY_MODE', 'sandbox'),
    'default_currency' => env('PAYIFY_CURRENCY', 'BDT'),
    'throw_exceptions' => env('PAYIFY_THROW'),
    'log_channel' => env('PAYIFY_LOG'),
    'table' => 'payify_transactions',
    'agreements_table' => 'payify_agreements',

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
            'app_secret', 'store_passwd', 'id_token', 'refresh_token',
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
        'bkash' => [
            'driver' => \DevWizard\Payify\Providers\Bkash\BkashDriver::class,
            'mode' => env('BKASH_MODE', 'sandbox'),
            'credentials' => [
                'app_key' => env('BKASH_APP_KEY'),
                'app_secret' => env('BKASH_APP_SECRET'),
                'username' => env('BKASH_USERNAME'),
                'password' => env('BKASH_PASSWORD'),
            ],
            'sandbox_url' => 'https://tokenized.sandbox.bka.sh/v1.2.0-beta',
            'live_url' => 'https://tokenized.pay.bka.sh/v1.2.0-beta',
            'cache_store' => env('BKASH_CACHE_STORE'),
            'token_safety_margin' => 60,
            'default_intent' => 'sale',
            'default_mode' => '0011',
            'agreement_callback_url' => env('BKASH_AGREEMENT_CALLBACK_URL'),
        ],

        'sslcommerz' => [
            'driver' => \DevWizard\Payify\Providers\Sslcommerz\SslcommerzDriver::class,
            'mode' => env('SSLCOMMERZ_MODE', 'sandbox'),
            'credentials' => [
                'store_id' => env('SSLCOMMERZ_STORE_ID'),
                'store_passwd' => env('SSLCOMMERZ_STORE_PASSWD'),
            ],
            'sandbox_url' => 'https://sandbox.sslcommerz.com',
            'live_url' => 'https://securepay.sslcommerz.com',
            'security' => [
                'verify_ip' => env('SSLCOMMERZ_VERIFY_IP', true),
                'allowed_ips_sandbox' => ['103.26.139.87'],
                'allowed_ips_live' => ['103.26.139.81', '103.132.153.81'],
                'verify_signature' => env('SSLCOMMERZ_VERIFY_SIGNATURE', true),
                'verify_validator' => env('SSLCOMMERZ_VERIFY_VALIDATOR', true),
            ],
            'defaults' => [
                'product_category' => env('SSLCOMMERZ_PRODUCT_CATEGORY', 'General'),
                'product_name' => env('SSLCOMMERZ_PRODUCT_NAME', 'Payment'),
                'product_profile' => env('SSLCOMMERZ_PRODUCT_PROFILE', 'general'),
            ],
            'embed' => [
                'sandbox_script' => 'https://sandbox.sslcommerz.com/embed.min.js?0.0.1',
                'live_script' => 'https://seamless-epay.sslcommerz.com/embed.min.js?0.0.1',
            ],
        ],
    ],
];

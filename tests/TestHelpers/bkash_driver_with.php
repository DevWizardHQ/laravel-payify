<?php

use DevWizard\Payify\Http\PayifyHttpClient;
use DevWizard\Payify\Providers\Bkash\BkashDriver;
use DevWizard\Payify\Providers\Bkash\BkashRequestBuilder;
use DevWizard\Payify\Providers\Bkash\BkashTokenManager;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

if (! function_exists('bkashDriverWith')) {
    function bkashDriverWith(MockHandler $mock, array $configOverride = []): BkashDriver
    {
        $config = array_merge([
            'mode' => 'sandbox',
            'credentials' => [
                'app_key' => 'AK', 'app_secret' => 'AS',
                'username' => 'U', 'password' => 'P',
            ],
            'sandbox_url' => 'https://tokenized.sandbox.bka.sh/v1.2.0-beta',
            'live_url' => 'https://tokenized.pay.bka.sh/v1.2.0-beta',
            'cache_store' => 'array',
            'token_safety_margin' => 60,
            'default_intent' => 'sale',
            'default_mode' => '0011',
        ], $configOverride);

        $client = new PayifyHttpClient([
            'timeout' => 5, 'retries' => 0, 'retry_delay' => 1,
            'mask_keys' => [], 'handler' => HandlerStack::create($mock),
        ], Log::getLogger());

        Cache::store('array')->put('payify:bkash:sandbox:id_token', 'cached.id.token', 3600);

        $tokens = new BkashTokenManager($client, $config);

        return new BkashDriver(
            client: $client,
            config: $config,
            events: app('events'),
            logger: Log::getLogger(),
            tokens: $tokens,
            requestBuilder: new BkashRequestBuilder,
        );
    }
}

<?php

use DevWizard\Payify\Http\PayifyHttpClient;
use DevWizard\Payify\Providers\Sslcommerz\SslcommerzDriver;
use DevWizard\Payify\Providers\Sslcommerz\SslcommerzGateway;
use DevWizard\Payify\Providers\Sslcommerz\SslcommerzIpnVerifier;
use DevWizard\Payify\Providers\Sslcommerz\SslcommerzPayloadBuilder;
use DevWizard\Payify\Providers\Sslcommerz\SslcommerzRefundClient;
use DevWizard\Payify\Providers\Sslcommerz\SslcommerzValidator;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Illuminate\Support\Facades\Log;

if (! function_exists('sslcommerzDriverWith')) {
    function sslcommerzDriverWith(MockHandler $mock, array $securityOverride = []): SslcommerzDriver
    {
        $config = [
            'mode' => 'sandbox',
            'credentials' => ['store_id' => 'testbox', 'store_passwd' => 'qwerty'],
            'sandbox_url' => 'https://sandbox.sslcommerz.com',
            'live_url' => 'https://securepay.sslcommerz.com',
            'security' => array_merge([
                'verify_ip' => false,
                'verify_signature' => false,
                'verify_validator' => false,
                'allowed_ips_sandbox' => ['103.26.139.87'],
                'allowed_ips_live' => ['103.26.139.81', '103.132.153.81'],
            ], $securityOverride),
            'defaults' => ['product_category' => 'General', 'product_profile' => 'general'],
            'embed' => [
                'sandbox_script' => 'https://sandbox.sslcommerz.com/embed.min.js?0.0.1',
                'live_script' => 'https://seamless-epay.sslcommerz.com/embed.min.js?0.0.1',
            ],
        ];

        $client = new PayifyHttpClient([
            'timeout' => 5, 'retries' => 0, 'retry_delay' => 1,
            'mask_keys' => [], 'handler' => HandlerStack::create($mock),
        ], Log::getLogger());

        $validator = new SslcommerzValidator($client, $config);
        $payloadBuilder = new SslcommerzPayloadBuilder($config);
        $gateway = new SslcommerzGateway($client, $config, $payloadBuilder);
        $refundClient = new SslcommerzRefundClient($client, $config);
        $ipnVerifier = new SslcommerzIpnVerifier($config, $validator);

        return new SslcommerzDriver(
            client: $client,
            config: $config,
            events: app('events'),
            logger: Log::getLogger(),
            gateway: $gateway,
            validator: $validator,
            refundClient: $refundClient,
            ipnVerifier: $ipnVerifier,
            payloadBuilder: $payloadBuilder,
        );
    }
}

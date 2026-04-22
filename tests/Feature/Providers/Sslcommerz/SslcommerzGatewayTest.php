<?php

use DevWizard\Payify\Dto\Customer;
use DevWizard\Payify\Dto\PaymentRequest;
use DevWizard\Payify\Http\PayifyHttpClient;
use DevWizard\Payify\Providers\Sslcommerz\SslcommerzGateway;
use DevWizard\Payify\Providers\Sslcommerz\SslcommerzPayloadBuilder;
use DevWizard\Payify\Tests\Fixtures\FixtureLoader;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Illuminate\Support\Facades\Log;

function sslGateway(MockHandler $mock): SslcommerzGateway
{
    $config = [
        'mode' => 'sandbox',
        'credentials' => ['store_id' => 'testbox', 'store_passwd' => 'qwerty'],
        'sandbox_url' => 'https://sandbox.sslcommerz.com',
        'live_url' => 'https://securepay.sslcommerz.com',
        'defaults' => ['product_category' => 'General', 'product_profile' => 'general'],
    ];
    $client = new PayifyHttpClient([
        'timeout' => 5, 'retries' => 0, 'retry_delay' => 1,
        'mask_keys' => [], 'handler' => HandlerStack::create($mock),
    ], Log::getLogger());

    return new SslcommerzGateway($client, $config, new SslcommerzPayloadBuilder($config));
}

it('initiates session and returns GatewayPageURL', function () {
    $mock = new MockHandler([FixtureLoader::json('Sslcommerz/init-success.json')]);
    $gateway = sslGateway($mock);

    $result = $gateway->initSession(new PaymentRequest(
        amount: 1000, currency: 'BDT', reference: 'INV-GW-1',
        customer: new Customer(name: 'X', email: 'x@y.z', phone: '017', city: 'Dhaka', country: 'BD'),
        callbackUrl: 'https://app.test/cb',
    ));

    expect($result['GatewayPageURL'])->toContain('sandbox.sslcommerz.com');
    expect($result['sessionkey'])->not->toBeEmpty();
});

it('selects a specific gateway when requested', function () {
    $mock = new MockHandler([FixtureLoader::json('Sslcommerz/init-success.json')]);
    $gateway = sslGateway($mock);

    $result = $gateway->initSession(new PaymentRequest(
        amount: 1000, currency: 'BDT', reference: 'INV-GW-VISA',
        customer: new Customer(name: 'X', email: 'x@y.z', phone: '017', city: 'Dhaka', country: 'BD'),
        callbackUrl: 'https://app.test/cb',
        gateway: 'visacard',
    ));

    expect($result['redirectUrl'])->toContain('/redirect/visa/');
});

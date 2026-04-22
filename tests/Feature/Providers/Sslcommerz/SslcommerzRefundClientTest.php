<?php

use DevWizard\Payify\Http\PayifyHttpClient;
use DevWizard\Payify\Providers\Sslcommerz\SslcommerzRefundClient;
use DevWizard\Payify\Tests\Fixtures\FixtureLoader;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Illuminate\Support\Facades\Log;

function sslRefundClientWith(MockHandler $mock): SslcommerzRefundClient
{
    $client = new PayifyHttpClient([
        'timeout' => 5, 'retries' => 0, 'retry_delay' => 1,
        'mask_keys' => [], 'handler' => HandlerStack::create($mock),
    ], Log::getLogger());

    return new SslcommerzRefundClient($client, [
        'mode' => 'sandbox',
        'sandbox_url' => 'https://sandbox.sslcommerz.com',
        'live_url' => 'https://securepay.sslcommerz.com',
        'credentials' => ['store_id' => 'S', 'store_passwd' => 'P'],
    ]);
}

it('initiates a refund', function () {
    $mock = new MockHandler([FixtureLoader::json('Sslcommerz/refund-initiate-done.json')]);
    $client = sslRefundClientWith($mock);

    $result = $client->initiate(
        bankTranId: '151114130739GjJYaOhM5j3pGP4',
        refundTransId: 'REF-X',
        amount: 100.0,
        remarks: 'return',
    );

    expect($result['APIConnect'])->toBe('DONE');
    expect($result['refund_ref_id'])->toBe('REF-INV-SSL-1-20260422');
    expect($result['status'])->toBe('processing');
});

it('queries refund status', function () {
    $mock = new MockHandler([FixtureLoader::json('Sslcommerz/refund-query-refunded.json')]);
    $client = sslRefundClientWith($mock);

    $result = $client->query('REF-INV-SSL-1-20260422');

    expect($result['status'])->toBe('refunded');
});

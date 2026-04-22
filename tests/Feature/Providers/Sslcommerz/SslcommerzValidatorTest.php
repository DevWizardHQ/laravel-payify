<?php

use DevWizard\Payify\Http\PayifyHttpClient;
use DevWizard\Payify\Providers\Sslcommerz\SslcommerzValidator;
use DevWizard\Payify\Tests\Fixtures\FixtureLoader;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Illuminate\Support\Facades\Log;

function sslValidatorWith(MockHandler $mock): SslcommerzValidator
{
    $client = new PayifyHttpClient([
        'timeout' => 5, 'retries' => 0, 'retry_delay' => 1,
        'mask_keys' => [], 'handler' => HandlerStack::create($mock),
    ], Log::getLogger());

    return new SslcommerzValidator($client, [
        'mode' => 'sandbox',
        'sandbox_url' => 'https://sandbox.sslcommerz.com',
        'live_url' => 'https://securepay.sslcommerz.com',
        'credentials' => ['store_id' => 'S', 'store_passwd' => 'P'],
    ]);
}

it('validates by val_id', function () {
    $mock = new MockHandler([FixtureLoader::json('Sslcommerz/validator-valid.json')]);
    $validator = sslValidatorWith($mock);

    $result = $validator->validateByValId('VAL123');

    expect($result['status'])->toBe('VALID');
    expect($result['tran_id'])->toBe('INV-SSL-1');
});

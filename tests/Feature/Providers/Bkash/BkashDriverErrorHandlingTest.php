<?php

use DevWizard\Payify\Dto\Customer;
use DevWizard\Payify\Dto\PaymentRequest;
use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Models\Transaction;
use DevWizard\Payify\Tests\Fixtures\FixtureLoader;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);
require_once __DIR__.'/../../../TestHelpers/bkash_driver_with.php';

beforeEach(fn () => Cache::store('array')->flush());

it('maps 2116 already-completed to BKASH_EXECUTE_ error during callback', function () {
    $mock = new MockHandler([FixtureLoader::json('Bkash/execute-error-2116.json')]);
    $driver = bkashDriverWith($mock);

    Transaction::create([
        'provider' => 'bkash', 'reference' => 'INV-AC', 'amount' => 100,
        'currency' => 'BDT', 'status' => TransactionStatus::Processing,
        'provider_transaction_id' => 'TR-AC',
    ]);

    $response = $driver->handleCallback(Request::create('/cb', 'GET', [
        'paymentID' => 'TR-AC', 'status' => 'success',
    ]));

    expect($response->errorCode)->toContain('BKASH_');
});

it('retries once on 2079 invalid token', function () {
    Cache::store('array')->put('payify:bkash:sandbox:id_token', 'stale.token', 3600);

    $mock = new MockHandler([
        new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'statusCode' => '2079', 'statusMessage' => 'Invalid App Token',
        ])),
        FixtureLoader::json('Bkash/grant-token-success.json'),
        FixtureLoader::json('Bkash/create-payment-success.json'),
    ]);
    $driver = bkashDriverWith($mock);

    $resp = $driver->pay(new PaymentRequest(
        amount: 100.50, currency: 'BDT', reference: 'INV-RETRY',
        customer: new Customer(phone: '017'),
        callbackUrl: 'https://cb',
    ));

    expect($resp->providerTransactionId)->toBe('TR0011sandbox123');
});

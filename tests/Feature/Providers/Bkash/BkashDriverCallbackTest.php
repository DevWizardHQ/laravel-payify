<?php

use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Events\PaymentSucceeded;
use DevWizard\Payify\Models\Transaction;
use DevWizard\Payify\Tests\Fixtures\FixtureLoader;
use GuzzleHttp\Handler\MockHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);
require_once __DIR__.'/../../../TestHelpers/bkash_driver_with.php';

it('calls execute on success callback and marks transaction succeeded', function () {
    Event::fake([PaymentSucceeded::class]);

    $mock = new MockHandler([FixtureLoader::json('Bkash/execute-success.json')]);
    $driver = bkashDriverWith($mock);

    Transaction::create([
        'provider' => 'bkash', 'reference' => 'INV-CB', 'amount' => 100.50,
        'currency' => 'BDT', 'status' => TransactionStatus::Processing,
        'provider_transaction_id' => 'TR0011sandbox123',
    ]);

    $response = $driver->handleCallback(Request::create('/cb', 'GET', [
        'paymentID' => 'TR0011sandbox123', 'status' => 'success',
    ]));

    expect($response->status)->toBe(TransactionStatus::Succeeded);
    $fresh = Transaction::where('reference', 'INV-CB')->first();
    expect((string) data_get($fresh->response_payload, 'trxID'))->toBe('ABC123XYZ');
    Event::assertDispatched(PaymentSucceeded::class);
});

it('returns failed when status param is missing without calling execute', function () {
    $mock = new MockHandler([]); // no HTTP calls expected
    $driver = bkashDriverWith($mock);

    Transaction::create([
        'provider' => 'bkash', 'reference' => 'INV-NO-STATUS', 'amount' => 100,
        'currency' => 'BDT', 'status' => TransactionStatus::Processing,
        'provider_transaction_id' => 'TR-NO-STATUS',
    ]);

    $response = $driver->handleCallback(Request::create('/cb', 'GET', [
        'paymentID' => 'TR-NO-STATUS',
        // no 'status' param
    ]));

    expect($response->status)->toBe(TransactionStatus::Cancelled);
});

it('marks cancelled without calling execute on status=cancel', function () {
    $mock = new MockHandler([]);
    $driver = bkashDriverWith($mock);

    Transaction::create([
        'provider' => 'bkash', 'reference' => 'INV-XC', 'amount' => 100,
        'currency' => 'BDT', 'status' => TransactionStatus::Processing,
        'provider_transaction_id' => 'TR-CANCEL',
    ]);

    $response = $driver->handleCallback(Request::create('/cb', 'GET', [
        'paymentID' => 'TR-CANCEL', 'status' => 'cancel',
    ]));

    expect($response->status)->toBe(TransactionStatus::Cancelled);
});

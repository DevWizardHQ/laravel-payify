<?php

use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Models\Transaction;
use DevWizard\Payify\Tests\Fixtures\FixtureLoader;
use GuzzleHttp\Handler\MockHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);
require_once __DIR__.'/../../../TestHelpers/sslcommerz_driver_with.php';

it('marks succeeded via validator when IPN has not run', function () {
    $mock = new MockHandler([FixtureLoader::json('Sslcommerz/validator-valid.json')]);
    $driver = sslcommerzDriverWith($mock);

    Transaction::create([
        'provider' => 'sslcommerz', 'reference' => 'INV-SSL-1', 'amount' => 1000,
        'currency' => 'BDT', 'status' => TransactionStatus::Processing,
    ]);

    $response = $driver->handleCallback(Request::create('/cb', 'POST', [
        'tran_id' => 'INV-SSL-1',
        'val_id' => 'VAL123',
    ]));

    expect($response->status)->toBe(TransactionStatus::Succeeded);
});

it('returns current state when IPN already processed', function () {
    $mock = new MockHandler([]);
    $driver = sslcommerzDriverWith($mock);

    Transaction::create([
        'provider' => 'sslcommerz', 'reference' => 'INV-SSL-WH', 'amount' => 500,
        'currency' => 'BDT', 'status' => TransactionStatus::Succeeded,
        'webhook_verified_at' => now(),
    ]);

    $response = $driver->handleCallback(Request::create('/cb', 'POST', [
        'tran_id' => 'INV-SSL-WH', 'val_id' => 'VALX',
    ]));

    expect($response->status)->toBe(TransactionStatus::Succeeded);
});

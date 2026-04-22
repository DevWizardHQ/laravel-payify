<?php

use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Models\Transaction;
use DevWizard\Payify\Tests\Fixtures\FixtureLoader;
use GuzzleHttp\Handler\MockHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
require_once __DIR__.'/../../../TestHelpers/bkash_driver_with.php';

it('queries status and updates transaction', function () {
    $mock = new MockHandler([FixtureLoader::json('Bkash/status-completed.json')]);
    $driver = bkashDriverWith($mock);

    $txn = Transaction::create([
        'provider' => 'bkash', 'reference' => 'INV-S', 'amount' => 100.50,
        'currency' => 'BDT', 'status' => TransactionStatus::Processing,
        'provider_transaction_id' => 'TR0011sandbox123',
    ]);

    $resp = $driver->status($txn);

    expect($resp->status)->toBe(TransactionStatus::Succeeded);
    expect($resp->providerTransactionId)->toBe('ABC123XYZ');
});

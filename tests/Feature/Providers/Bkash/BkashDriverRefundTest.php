<?php

use DevWizard\Payify\Dto\RefundRequest;
use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Events\PaymentRefunded;
use DevWizard\Payify\Exceptions\RefundFailedException;
use DevWizard\Payify\Models\Transaction;
use DevWizard\Payify\Tests\Fixtures\FixtureLoader;
use GuzzleHttp\Handler\MockHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);
require_once __DIR__.'/../../../TestHelpers/bkash_driver_with.php';

it('processes partial refund', function () {
    Event::fake([PaymentRefunded::class]);
    $mock = new MockHandler([FixtureLoader::json('Bkash/refund-success.json')]);
    $driver = bkashDriverWith($mock);

    $txn = Transaction::create([
        'provider' => 'bkash', 'reference' => 'INV-RF', 'amount' => 100,
        'currency' => 'BDT', 'status' => TransactionStatus::Succeeded,
        'provider_transaction_id' => 'TR0011sandbox123',
        'response_payload' => ['trxID' => 'ABC123XYZ'],
    ]);

    $resp = $driver->refund(new RefundRequest(
        transactionId: $txn->id,
        amount: 25,
        reason: 'return',
    ));

    expect($resp->amount)->toBe(25.0);
    expect($txn->fresh()->status)->toBe(TransactionStatus::PartiallyRefunded);
    Event::assertDispatched(PaymentRefunded::class);
});

it('throws refund exception on 2071', function () {
    $mock = new MockHandler([FixtureLoader::json('Bkash/refund-error-2071.json')]);
    $driver = bkashDriverWith($mock);

    $txn = Transaction::create([
        'provider' => 'bkash', 'reference' => 'INV-2071', 'amount' => 100,
        'currency' => 'BDT', 'status' => TransactionStatus::Succeeded,
        'provider_transaction_id' => 'TR-OLD',
        'response_payload' => ['trxID' => 'OLD'],
    ]);

    expect(fn () => $driver->refund(new RefundRequest(transactionId: $txn->id)))
        ->toThrow(RefundFailedException::class);
});

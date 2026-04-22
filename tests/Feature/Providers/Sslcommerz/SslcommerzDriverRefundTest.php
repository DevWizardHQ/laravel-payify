<?php

use DevWizard\Payify\Dto\RefundRequest;
use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Events\PaymentRefunded;
use DevWizard\Payify\Models\Transaction;
use DevWizard\Payify\Tests\Fixtures\FixtureLoader;
use GuzzleHttp\Handler\MockHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);
require_once __DIR__.'/../../../TestHelpers/sslcommerz_driver_with.php';

it('stores refund_ref_id without dispatching PaymentRefunded on processing status', function () {
    Event::fake([PaymentRefunded::class]);
    $mock = new MockHandler([FixtureLoader::json('Sslcommerz/refund-initiate-processing.json')]);
    $driver = sslcommerzDriverWith($mock);

    $txn = Transaction::create([
        'provider' => 'sslcommerz', 'reference' => 'INV-RF', 'amount' => 1000,
        'currency' => 'BDT', 'status' => TransactionStatus::Succeeded,
        'provider_transaction_id' => 'BT-IN-PROCESS',
    ]);

    $resp = $driver->refund(new RefundRequest(transactionId: $txn->id, amount: 500, reason: 'return'));

    expect($resp->amount)->toBe(500.0);
    $fresh = $txn->fresh();
    expect(data_get($fresh->response_payload, 'refund.refund_ref_id'))->toBe('REF-RP-1');
    // Processing is deferred — finalization happens via payify:refund:status polling.
    Event::assertNotDispatched(PaymentRefunded::class);
});

it('does not dispatch PaymentRefunded when refund is cancelled', function () {
    Event::fake([PaymentRefunded::class]);
    $mock = new MockHandler([FixtureLoader::json('Sslcommerz/refund-initiate-cancelled.json')]);
    $driver = sslcommerzDriverWith($mock);

    $txn = Transaction::create([
        'provider' => 'sslcommerz', 'reference' => 'INV-RC', 'amount' => 1000,
        'currency' => 'BDT', 'status' => TransactionStatus::Succeeded,
        'provider_transaction_id' => 'BT-CANCEL',
    ]);

    $driver->refund(new RefundRequest(transactionId: $txn->id, amount: 500, reason: 'return'));

    Event::assertNotDispatched(PaymentRefunded::class);
});

it('queries refund status', function () {
    $mock = new MockHandler([FixtureLoader::json('Sslcommerz/refund-query-refunded.json')]);
    $driver = sslcommerzDriverWith($mock);

    $result = $driver->queryRefund('REF-1');

    expect($result['status'])->toBe('refunded');
});

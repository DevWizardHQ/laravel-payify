<?php

use DevWizard\Payify\Dto\PayoutRequest;
use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Events\PayoutInitiated;
use DevWizard\Payify\Events\PayoutSucceeded;
use DevWizard\Payify\Models\Transaction;
use DevWizard\Payify\Tests\Fixtures\FixtureLoader;
use GuzzleHttp\Handler\MockHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);
require_once __DIR__.'/../../../TestHelpers/bkash_driver_with.php';

it('runs B2B payout end-to-end', function () {
    Event::fake([PayoutInitiated::class, PayoutSucceeded::class]);
    $mock = new MockHandler([
        FixtureLoader::json('Bkash/payout-init-success.json'),
        FixtureLoader::json('Bkash/payout-execute-success.json'),
    ]);
    $driver = bkashDriverWith($mock);

    $resp = $driver->payout(new PayoutRequest(
        reference: 'PO-1',
        amount: 5000,
        currency: 'BDT',
        receiverIdentifier: '01712345678',
    ));

    expect($resp->status)->toBe(TransactionStatus::Succeeded);
    expect($resp->providerPayoutId)->toBe('PO_INIT_123');
    expect(Transaction::where('type', 'payout')->where('reference', 'PO-1')->exists())->toBeTrue();
    Event::assertDispatched(PayoutInitiated::class);
    Event::assertDispatched(PayoutSucceeded::class);
});

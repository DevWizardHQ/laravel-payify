<?php

use DevWizard\Payify\Dto\Customer;
use DevWizard\Payify\Dto\PaymentRequest;
use DevWizard\Payify\Events\AgreementCancelled;
use DevWizard\Payify\Models\Agreement;
use DevWizard\Payify\Models\Transaction;
use DevWizard\Payify\Tests\Fixtures\FixtureLoader;
use GuzzleHttp\Handler\MockHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);
require_once __DIR__.'/../../../TestHelpers/bkash_driver_with.php';

it('tokenize() creates agreement-init payment', function () {
    $mock = new MockHandler([FixtureLoader::json('Bkash/agreement-create-success.json')]);
    $driver = bkashDriverWith($mock);

    $resp = $driver->tokenize(new Customer(phone: '01700000000'));

    expect($resp->token)->toBe('AGR-INIT-123');
});

it('tokenize() records a transaction so the agreement callback can resolve it', function () {
    $mock = new MockHandler([FixtureLoader::json('Bkash/agreement-create-success.json')]);
    $driver = bkashDriverWith($mock);

    $driver->tokenize(new Customer(phone: '01700000000'));

    $txn = Transaction::where('provider', 'bkash')
        ->where('provider_transaction_id', 'AGR-INIT-123')
        ->first();

    expect($txn)->not->toBeNull();
    expect($txn->type)->toBe('agreement_create');
});

it('chargeToken issues mode=0001 payment', function () {
    $mock = new MockHandler([FixtureLoader::json('Bkash/create-payment-success.json')]);
    $driver = bkashDriverWith($mock);

    Agreement::create([
        'provider' => 'bkash', 'agreement_id' => 'AGR123ABC',
        'payer_reference' => '01700000000', 'status' => 'active',
    ]);

    $resp = $driver->chargeToken('AGR123ABC', new PaymentRequest(
        amount: 500, currency: 'BDT', reference: 'INV-RE',
        callbackUrl: 'https://cb',
    ));

    expect($resp->providerTransactionId)->toBe('TR0011sandbox123');
});

it('detokenize cancels agreement', function () {
    Event::fake([AgreementCancelled::class]);
    $mock = new MockHandler([FixtureLoader::json('Bkash/agreement-cancel-success.json')]);
    $driver = bkashDriverWith($mock);

    Agreement::create([
        'provider' => 'bkash', 'agreement_id' => 'AGR-CANX',
        'payer_reference' => '017', 'status' => 'active',
    ]);

    $ok = $driver->detokenize('AGR-CANX');

    expect($ok)->toBeTrue();
    expect(Agreement::where('agreement_id', 'AGR-CANX')->first()->status)->toBe('cancelled');
    Event::assertDispatched(AgreementCancelled::class);
});

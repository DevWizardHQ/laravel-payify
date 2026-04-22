<?php

use DevWizard\Payify\Dto\Customer;
use DevWizard\Payify\Dto\PaymentRequest;
use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Events\PaymentInitiated;
use DevWizard\Payify\Models\Transaction;
use DevWizard\Payify\Tests\Fixtures\FixtureLoader;
use GuzzleHttp\Handler\MockHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

require_once __DIR__.'/../../../TestHelpers/bkash_driver_with.php';

beforeEach(fn () => Cache::store('array')->flush());

it('creates a bkash payment and records transaction', function () {
    Event::fake([PaymentInitiated::class]);
    $mock = new MockHandler([FixtureLoader::json('Bkash/create-payment-success.json')]);
    $driver = bkashDriverWith($mock);

    $response = $driver->pay(new PaymentRequest(
        amount: 100.50,
        currency: 'BDT',
        reference: 'INV-1',
        customer: new Customer(phone: '01700000000'),
        callbackUrl: 'https://app.test/cb',
    ));

    expect($response->status)->toBe(TransactionStatus::Processing);
    expect($response->redirectUrl)->toContain('sandbox.payment.bkash.com');
    expect($response->providerTransactionId)->toBe('TR0011sandbox123');
    expect(Transaction::where('reference', 'INV-1')->exists())->toBeTrue();
    Event::assertDispatched(PaymentInitiated::class);
});

it('returns existing transaction on idempotent pay', function () {
    $mock = new MockHandler([FixtureLoader::json('Bkash/create-payment-success.json')]);
    $driver = bkashDriverWith($mock);

    $req = new PaymentRequest(
        amount: 100.50, currency: 'BDT', reference: 'INV-IDEMP',
        customer: new Customer(phone: '017'),
        callbackUrl: 'https://cb',
    );

    $driver->pay($req);
    $driver->pay($req);

    expect(Transaction::where('reference', 'INV-IDEMP')->count())->toBe(1);
});

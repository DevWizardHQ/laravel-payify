<?php

use DevWizard\Payify\Dto\Customer;
use DevWizard\Payify\Dto\PaymentRequest;
use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Events\PaymentInitiated;
use DevWizard\Payify\Exceptions\PaymentFailedException;
use DevWizard\Payify\Models\Transaction;
use DevWizard\Payify\Tests\Fixtures\FixtureLoader;
use GuzzleHttp\Handler\MockHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);
require_once __DIR__.'/../../../TestHelpers/sslcommerz_driver_with.php';

it('creates a sslcommerz session', function () {
    Event::fake([PaymentInitiated::class]);
    $mock = new MockHandler([FixtureLoader::json('Sslcommerz/init-success.json')]);
    $driver = sslcommerzDriverWith($mock);

    $response = $driver->pay(new PaymentRequest(
        amount: 1000, currency: 'BDT', reference: 'INV-SSL-1',
        customer: new Customer(name: 'X', email: 'x@y.z', phone: '017', city: 'Dhaka', country: 'BD'),
        callbackUrl: 'https://app.test/cb',
    ));

    expect($response->status)->toBe(TransactionStatus::Processing);
    expect($response->redirectUrl)->toContain('sandbox.sslcommerz.com');
    expect($response->sessionId)->not->toBeEmpty();
    expect(Transaction::where('reference', 'INV-SSL-1')->exists())->toBeTrue();
    Event::assertDispatched(PaymentInitiated::class);
});

it('throws on FAILED init', function () {
    config()->set('payify.throw_exceptions', true);
    $mock = new MockHandler([FixtureLoader::json('Sslcommerz/init-failed.json')]);
    $driver = sslcommerzDriverWith($mock);

    expect(fn () => $driver->pay(new PaymentRequest(
        amount: 10, currency: 'BDT', reference: 'FAIL-1',
        customer: new Customer(name: 'X', email: 'x@y.z', phone: '017'),
        callbackUrl: 'https://app.test/cb',
    )))->toThrow(PaymentFailedException::class);
});

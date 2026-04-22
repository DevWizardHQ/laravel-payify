<?php

use DevWizard\Payify\Drivers\FakeDriver;
use DevWizard\Payify\Dto\Customer;
use DevWizard\Payify\Dto\PaymentRequest;
use DevWizard\Payify\Dto\RefundRequest;
use DevWizard\Payify\Dto\WebhookPayload;
use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Events\PaymentInitiated;
use DevWizard\Payify\Events\PaymentSucceeded;
use DevWizard\Payify\Exceptions\UnsupportedOperationException;
use DevWizard\Payify\Http\PayifyHttpClient;
use DevWizard\Payify\Models\Transaction;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

function makeFakeDriver(array $config = []): FakeDriver
{
    $client = new PayifyHttpClient([
        'timeout' => 1, 'retries' => 0, 'retry_delay' => 1,
        'mask_keys' => [], 'handler' => HandlerStack::create(new MockHandler([])),
    ], Log::getLogger());

    $defaults = ['mode' => 'sandbox', 'credentials' => []];

    return new FakeDriver(
        client: $client,
        config: array_merge($defaults, $config),
        events: app('events'),
        logger: Log::getLogger(),
    );
}

it('creates transaction and returns canned payment response', function () {
    Event::fake([PaymentInitiated::class, PaymentSucceeded::class]);
    $driver = makeFakeDriver();

    $response = $driver->pay(new PaymentRequest(
        amount: 100, currency: 'BDT', reference: 'INV-F1',
        customer: new Customer(name: 'Fake'),
    ));

    expect($response->status)->toBe(TransactionStatus::Processing);
    expect($response->redirectUrl)->toStartWith('https://fake.payify.test/');
    expect(Transaction::where('reference', 'INV-F1')->exists())->toBeTrue();
    Event::assertDispatched(PaymentInitiated::class);
});

it('returns existing transaction on idempotent pay', function () {
    $driver = makeFakeDriver();
    $req = new PaymentRequest(amount: 10, currency: 'BDT', reference: 'INV-IDE');

    $first = $driver->pay($req);
    $second = $driver->pay($req);

    expect($first->transactionId)->toBe($second->transactionId);
    expect(Transaction::where('reference', 'INV-IDE')->count())->toBe(1);
});

it('processes refund', function () {
    $driver = makeFakeDriver();
    $driver->pay(new PaymentRequest(amount: 100, currency: 'BDT', reference: 'INV-RF'));
    $txn = Transaction::where('reference', 'INV-RF')->first();
    $txn->markSucceeded('pay_x');

    $refund = $driver->refund(new RefundRequest(transactionId: $txn->id, amount: 50));

    expect($refund->amount)->toBe(50.0);
    expect($refund->status)->toBe(TransactionStatus::Refunded);
});

it('rejects refund when transaction is not in refundable state', function () {
    $driver = makeFakeDriver();
    $txn = Transaction::create([
        'provider' => 'fake', 'reference' => 'INV-NRF', 'amount' => 100,
        'currency' => 'BDT', 'status' => TransactionStatus::Failed,
    ]);

    $driver->refund(new RefundRequest(transactionId: $txn->id, amount: 100));
})->throws(UnsupportedOperationException::class);

it('verifies webhooks unconditionally', function () {
    $driver = makeFakeDriver();
    $req = new Request(query: [], request: [
        'event' => 'payment.succeeded',
        'reference' => 'INV-WH',
        'provider_transaction_id' => 'pay_1',
        'amount' => 100,
        'currency' => 'BDT',
    ]);

    $payload = $driver->verifyWebhook($req);

    expect($payload)->toBeInstanceOf(WebhookPayload::class);
    expect($payload->event)->toBe('payment.succeeded');
    expect($payload->reference)->toBe('INV-WH');
    expect($payload->verified)->toBeTrue();
});

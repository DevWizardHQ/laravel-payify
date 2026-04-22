<?php

use DevWizard\Payify\Drivers\FakeDriver;
use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Events\PaymentSucceeded;
use DevWizard\Payify\Events\WebhookReceived;
use DevWizard\Payify\Jobs\ProcessWebhookJob;
use DevWizard\Payify\Models\Transaction;
use DevWizard\Payify\Tests\Fixtures\NonWebhookDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('payify.default', 'fake');
    config()->set('payify.providers.fake', [
        'driver' => FakeDriver::class,
        'mode' => 'sandbox',
        'credentials' => [],
    ]);
    config()->set('payify.routes.middleware', []);
});

it('accepts verified webhook and marks transaction succeeded', function () {
    Event::fake([PaymentSucceeded::class, WebhookReceived::class]);

    $txn = Transaction::create([
        'provider' => 'fake', 'reference' => 'INV-WH', 'amount' => 100,
        'currency' => 'BDT', 'status' => TransactionStatus::Pending,
    ]);

    $response = $this->postJson('/payify/webhook/fake', [
        'event' => 'payment.succeeded',
        'reference' => 'INV-WH',
        'provider_transaction_id' => 'pay_1',
        'amount' => 100,
        'currency' => 'BDT',
    ]);

    $response->assertOk();
    expect($txn->fresh()->status)->toBe(TransactionStatus::Succeeded);
    expect($txn->fresh()->webhook_verified_at)->not->toBeNull();
    Event::assertDispatched(PaymentSucceeded::class);
    Event::assertDispatched(WebhookReceived::class);
});

it('queues webhook processing when configured', function () {
    Queue::fake();

    config()->set('payify.webhooks.queue', 'webhooks');

    $this->postJson('/payify/webhook/fake', [
        'event' => 'payment.succeeded',
        'reference' => 'INV-WHQ',
    ])->assertOk();

    Queue::assertPushedOn('webhooks', ProcessWebhookJob::class);
});

it('does not overwrite a terminal transaction on duplicate webhook', function () {
    Event::fake([PaymentSucceeded::class, WebhookReceived::class]);

    $txn = Transaction::create([
        'provider' => 'fake', 'reference' => 'INV-TERM', 'amount' => 100,
        'currency' => 'BDT', 'status' => TransactionStatus::Succeeded,
        'paid_at' => now()->subHour(),
    ]);
    $originalPaidAt = $txn->paid_at;

    $this->postJson('/payify/webhook/fake', [
        'event' => 'payment.failed',
        'reference' => 'INV-TERM',
        'error_code' => 'LATE',
        'error_message' => 'too late',
    ])->assertOk();

    $fresh = $txn->fresh();
    expect($fresh->status)->toBe(TransactionStatus::Succeeded);
    expect($fresh->paid_at?->eq($originalPaidAt))->toBeTrue();
    Event::assertNotDispatched(PaymentSucceeded::class);
});

it('does not double-count refunds when webhook replays after full refund', function () {
    $txn = Transaction::create([
        'provider' => 'fake', 'reference' => 'INV-REF', 'amount' => 100,
        'currency' => 'BDT', 'status' => TransactionStatus::Refunded,
        'refunded_amount' => 100,
    ]);

    $this->postJson('/payify/webhook/fake', [
        'event' => 'payment.refunded',
        'reference' => 'INV-REF',
        'amount' => 100,
    ])->assertOk();

    $fresh = $txn->fresh();
    expect((float) $fresh->refunded_amount)->toBe(100.0);
    expect($fresh->status)->toBe(TransactionStatus::Refunded);
});

it('returns 400 for unsupported provider', function () {
    config()->set('payify.providers.plain', [
        'driver' => NonWebhookDriver::class,
        'mode' => 'sandbox',
        'credentials' => [],
    ]);

    $this->postJson('/payify/webhook/plain', [])->assertStatus(400);
});

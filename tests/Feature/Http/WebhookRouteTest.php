<?php

use DevWizard\Payify\Drivers\FakeDriver;
use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Events\PaymentSucceeded;
use DevWizard\Payify\Events\WebhookReceived;
use DevWizard\Payify\Jobs\ProcessWebhookJob;
use DevWizard\Payify\Models\Transaction;
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

it('returns 400 for unsupported provider', function () {
    config()->set('payify.providers.plain', [
        'driver' => \DevWizard\Payify\Tests\Fixtures\NonWebhookDriver::class,
        'mode' => 'sandbox',
        'credentials' => [],
    ]);

    $this->postJson('/payify/webhook/plain', [])->assertStatus(400);
});

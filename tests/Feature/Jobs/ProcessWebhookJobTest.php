<?php

use DevWizard\Payify\Dto\WebhookPayload;
use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Events\WebhookReceived;
use DevWizard\Payify\Jobs\ProcessWebhookJob;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('fires WebhookReceived when run', function () {
    Event::fake([WebhookReceived::class]);

    $txn = Transaction::create([
        'provider' => 'fake', 'reference' => 'INV-J', 'amount' => 10,
        'currency' => 'BDT', 'status' => TransactionStatus::Pending,
    ]);

    $payload = new WebhookPayload(
        provider: 'fake', event: 'payment.succeeded',
        providerTransactionId: 'p_1', reference: 'INV-J',
        amount: 10, currency: 'BDT', raw: [], verified: true,
    );

    (new ProcessWebhookJob($payload, $txn->id))->handle();

    Event::assertDispatched(
        WebhookReceived::class,
        fn (WebhookReceived $event) => $event->payload->reference === 'INV-J' && $event->transaction?->id === $txn->id
    );
});

it('handles null transaction id', function () {
    Event::fake([WebhookReceived::class]);

    $payload = new WebhookPayload(
        provider: 'fake', event: 'unknown', providerTransactionId: null,
        reference: null, amount: null, currency: null, raw: [], verified: true,
    );

    (new ProcessWebhookJob($payload, null))->handle();

    Event::assertDispatched(
        WebhookReceived::class,
        fn (WebhookReceived $event) => $event->transaction === null
    );
});

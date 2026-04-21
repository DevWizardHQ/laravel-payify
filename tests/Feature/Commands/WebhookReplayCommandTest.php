<?php

use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Events\WebhookReceived;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('replays a stored webhook payload', function () {
    Event::fake([WebhookReceived::class]);

    $txn = Transaction::create([
        'provider' => 'fake', 'reference' => 'INV-RP', 'amount' => 100,
        'currency' => 'BDT', 'status' => TransactionStatus::Succeeded,
        'webhook_payload' => ['event' => 'payment.succeeded', 'amount' => 100],
    ]);

    $this->artisan('payify:webhook:replay', ['transaction_id' => $txn->id])
        ->assertSuccessful();

    Event::assertDispatched(WebhookReceived::class);
});

it('fails when no stored payload', function () {
    $txn = Transaction::create([
        'provider' => 'fake', 'reference' => 'INV-NOWH', 'amount' => 100,
        'currency' => 'BDT', 'status' => TransactionStatus::Succeeded,
    ]);

    $this->artisan('payify:webhook:replay', ['transaction_id' => $txn->id])
        ->assertFailed();
});

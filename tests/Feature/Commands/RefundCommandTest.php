<?php

use DevWizard\Payify\Drivers\FakeDriver;
use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('payify.providers.fake', [
        'driver' => FakeDriver::class,
        'mode' => 'sandbox',
        'credentials' => [],
    ]);
});

it('processes a full refund via CLI', function () {
    $txn = Transaction::create([
        'provider' => 'fake', 'reference' => 'INV-REF', 'amount' => 100,
        'currency' => 'BDT', 'status' => TransactionStatus::Succeeded,
    ]);

    $this->artisan('payify:refund', ['transaction_id' => $txn->id])
        ->expectsConfirmation("Refund full amount for transaction {$txn->id}?", 'yes')
        ->assertSuccessful();

    expect($txn->fresh()->status)->toBe(TransactionStatus::Refunded);
});

it('aborts refund when user declines confirmation', function () {
    $txn = Transaction::create([
        'provider' => 'fake', 'reference' => 'INV-NOREF', 'amount' => 100,
        'currency' => 'BDT', 'status' => TransactionStatus::Succeeded,
    ]);

    $this->artisan('payify:refund', ['transaction_id' => $txn->id])
        ->expectsConfirmation("Refund full amount for transaction {$txn->id}?", 'no')
        ->assertSuccessful();

    expect($txn->fresh()->status)->toBe(TransactionStatus::Succeeded);
});

it('fails when transaction is not in refundable state', function () {
    $txn = Transaction::create([
        'provider' => 'fake', 'reference' => 'INV-NRF', 'amount' => 100,
        'currency' => 'BDT', 'status' => TransactionStatus::Failed,
    ]);

    $this->artisan('payify:refund', ['transaction_id' => $txn->id])
        ->expectsOutputToContain('not in a refundable state')
        ->assertFailed();
});

it('processes a partial refund', function () {
    $txn = Transaction::create([
        'provider' => 'fake', 'reference' => 'INV-REFP', 'amount' => 100,
        'currency' => 'BDT', 'status' => TransactionStatus::Succeeded,
    ]);

    $this->artisan('payify:refund', ['transaction_id' => $txn->id, '--amount' => '30'])
        ->expectsConfirmation("Refund {$txn->currency} 30 for transaction {$txn->id}?", 'yes')
        ->assertSuccessful();

    expect($txn->fresh()->status)->toBe(TransactionStatus::PartiallyRefunded);
    expect((float) $txn->fresh()->refunded_amount)->toBe(30.0);
});

<?php

use DevWizard\Payify\Drivers\FakeDriver;
use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('queries transaction status via driver', function () {
    config()->set('payify.providers.fake', [
        'driver' => FakeDriver::class,
        'mode' => 'sandbox',
        'credentials' => [],
    ]);

    $txn = Transaction::create([
        'provider' => 'fake', 'reference' => 'INV-S', 'amount' => 100,
        'currency' => 'BDT', 'status' => TransactionStatus::Pending,
    ]);

    $this->artisan('payify:status', ['transaction_id' => $txn->id])
        ->expectsOutputToContain($txn->id)
        ->assertSuccessful();
});

it('fails for unknown transaction', function () {
    $this->artisan('payify:status', ['transaction_id' => 'missing'])
        ->assertFailed();
});

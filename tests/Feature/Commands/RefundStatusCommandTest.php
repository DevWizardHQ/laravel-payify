<?php

use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('fails when no refund_ref_id is stored', function () {
    $t = Transaction::create([
        'provider' => 'fake', 'reference' => 'NOREF', 'amount' => 100,
        'currency' => 'BDT', 'status' => TransactionStatus::Succeeded,
    ]);

    $this->artisan('payify:refund:status', ['transaction_id' => $t->id])
        ->assertFailed();
});

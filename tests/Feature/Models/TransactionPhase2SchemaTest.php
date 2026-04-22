<?php

use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('has phase 2 columns', function () {
    $columns = Schema::getColumnListing('payify_transactions');

    expect($columns)->toContain('type', 'intent', 'agreement_id', 'authorized_at', 'captured_at', 'voided_at');
});

it('defaults type to payment', function () {
    $t = Transaction::create([
        'provider' => 'fake', 'reference' => 'T2', 'amount' => 10,
        'currency' => 'BDT', 'status' => TransactionStatus::Pending,
    ]);
    expect($t->fresh()->type)->toBe('payment');
});

it('accepts payout type', function () {
    $t = Transaction::create([
        'provider' => 'fake', 'reference' => 'PO', 'amount' => 10,
        'currency' => 'BDT', 'status' => TransactionStatus::Pending,
        'type' => 'payout',
    ]);
    expect($t->fresh()->type)->toBe('payout');
});

<?php

use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a pending transaction with uuid primary key', function () {
    $t = Transaction::create([
        'provider' => 'bkash',
        'reference' => 'INV-1',
        'amount' => 100.00,
        'currency' => 'BDT',
        'status' => TransactionStatus::Pending,
    ]);

    expect($t->id)->toBeString();
    expect(strlen($t->id))->toBe(36);
    expect($t->status)->toBe(TransactionStatus::Pending);
});

it('casts amount and status correctly', function () {
    $t = Transaction::create([
        'provider' => 'bkash',
        'reference' => 'INV-2',
        'amount' => '250.50',
        'currency' => 'BDT',
        'status' => 'succeeded',
    ]);
    $fresh = $t->fresh();

    expect($fresh->status)->toBe(TransactionStatus::Succeeded);
    expect((string) $fresh->amount)->toBe('250.50');
});

it('marks transactions succeeded', function () {
    $t = Transaction::create([
        'provider' => 'bkash', 'reference' => 'r', 'amount' => 10,
        'currency' => 'BDT', 'status' => TransactionStatus::Pending,
    ]);

    $t->markSucceeded('pay_abc', ['ok' => true]);

    expect($t->fresh()->status)->toBe(TransactionStatus::Succeeded);
    expect($t->fresh()->provider_transaction_id)->toBe('pay_abc');
    expect($t->fresh()->paid_at)->not->toBeNull();
});

it('marks transactions failed with code/message', function () {
    $t = Transaction::create([
        'provider' => 'bkash', 'reference' => 'rf', 'amount' => 10,
        'currency' => 'BDT', 'status' => TransactionStatus::Pending,
    ]);

    $t->markFailed('E_BOOM', 'boom', ['raw' => 1]);

    expect($t->fresh()->status)->toBe(TransactionStatus::Failed);
    expect($t->fresh()->error_code)->toBe('E_BOOM');
    expect($t->fresh()->error_message)->toBe('boom');
    expect($t->fresh()->failed_at)->not->toBeNull();
});

it('marks full refunds', function () {
    $t = Transaction::create([
        'provider' => 'bkash', 'reference' => 'rr', 'amount' => 100,
        'currency' => 'BDT', 'status' => TransactionStatus::Succeeded,
    ]);

    $t->markRefunded(100.0, []);

    expect($t->fresh()->status)->toBe(TransactionStatus::Refunded);
    expect((float) $t->fresh()->refunded_amount)->toBe(100.0);
});

it('marks partial refunds', function () {
    $t = Transaction::create([
        'provider' => 'bkash', 'reference' => 'rp', 'amount' => 100,
        'currency' => 'BDT', 'status' => TransactionStatus::Succeeded,
    ]);

    $t->markRefunded(25.0, []);

    expect($t->fresh()->status)->toBe(TransactionStatus::PartiallyRefunded);
    expect((float) $t->fresh()->refunded_amount)->toBe(25.0);
});

it('calculates remaining refundable', function () {
    $t = Transaction::create([
        'provider' => 'bkash', 'reference' => 'rem', 'amount' => 100,
        'currency' => 'BDT', 'status' => TransactionStatus::Succeeded,
        'refunded_amount' => 30,
    ]);

    expect($t->remainingRefundable())->toBe(70.0);
});

it('soft deletes', function () {
    $t = Transaction::create([
        'provider' => 'bkash', 'reference' => 'sd', 'amount' => 1,
        'currency' => 'BDT', 'status' => TransactionStatus::Failed,
    ]);

    $t->delete();

    expect(Transaction::find($t->id))->toBeNull();
    expect(Transaction::withTrashed()->find($t->id))->not->toBeNull();
});

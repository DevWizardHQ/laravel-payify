<?php

use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Models\Agreement;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

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

it('marks transaction authorized', function () {
    $t = Transaction::create([
        'provider' => 'bkash', 'reference' => 'AU', 'amount' => 100,
        'currency' => 'BDT', 'status' => TransactionStatus::Pending,
        'intent' => 'authorization',
    ]);

    $t->markAuthorized('pay_abc', ['ok' => true]);

    expect($t->fresh()->status)->toBe(TransactionStatus::Processing);
    expect($t->fresh()->authorized_at)->not->toBeNull();
    expect($t->fresh()->provider_transaction_id)->toBe('pay_abc');
});

it('marks transaction captured', function () {
    $t = Transaction::create([
        'provider' => 'bkash', 'reference' => 'CA', 'amount' => 100,
        'currency' => 'BDT', 'status' => TransactionStatus::Processing,
        'authorized_at' => now(),
    ]);

    $t->markCaptured(100.0, []);

    expect($t->fresh()->status)->toBe(TransactionStatus::Succeeded);
    expect($t->fresh()->captured_at)->not->toBeNull();
    expect($t->fresh()->paid_at)->not->toBeNull();
});

it('marks transaction voided', function () {
    $t = Transaction::create([
        'provider' => 'bkash', 'reference' => 'VO', 'amount' => 100,
        'currency' => 'BDT', 'status' => TransactionStatus::Processing,
    ]);

    $t->markVoided([]);

    expect($t->fresh()->status)->toBe(TransactionStatus::Cancelled);
    expect($t->fresh()->voided_at)->not->toBeNull();
});

it('resolves agreement relation by string column + provider scope', function () {
    $a = Agreement::create([
        'provider' => 'bkash', 'agreement_id' => 'AGR-REL',
        'payer_reference' => '017', 'status' => 'active',
    ]);

    $t = Transaction::create([
        'provider' => 'bkash', 'reference' => 'AGR-T', 'amount' => 100,
        'currency' => 'BDT', 'status' => TransactionStatus::Succeeded,
        'agreement_id' => 'AGR-REL',
    ]);

    expect($t->agreement?->id)->toBe($a->id);
});

it('agreement relation returns null when agreement_id not set', function () {
    $t = Transaction::create([
        'provider' => 'bkash', 'reference' => 'NO-AGR', 'amount' => 100,
        'currency' => 'BDT', 'status' => TransactionStatus::Succeeded,
    ]);

    expect($t->agreement)->toBeNull();
});

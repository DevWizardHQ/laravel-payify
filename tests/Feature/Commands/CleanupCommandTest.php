<?php

use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('soft-deletes old pending transactions', function () {
    $old = Transaction::create([
        'provider' => 'fake', 'reference' => 'OLD', 'amount' => 1,
        'currency' => 'BDT', 'status' => TransactionStatus::Pending,
    ]);
    $old->created_at = now()->subDays(30);
    $old->updated_at = now()->subDays(30);
    $old->saveQuietly();

    $fresh = Transaction::create([
        'provider' => 'fake', 'reference' => 'FRESH', 'amount' => 1,
        'currency' => 'BDT', 'status' => TransactionStatus::Pending,
    ]);

    $this->artisan('payify:cleanup', ['--status' => 'pending', '--before' => '14'])
        ->assertSuccessful();

    expect(Transaction::find($old->id))->toBeNull();
    expect(Transaction::find($fresh->id))->not->toBeNull();
});

it('supports dry run', function () {
    $old = Transaction::create([
        'provider' => 'fake', 'reference' => 'DRY', 'amount' => 1,
        'currency' => 'BDT', 'status' => TransactionStatus::Pending,
    ]);
    $old->created_at = now()->subDays(30);
    $old->saveQuietly();

    $this->artisan('payify:cleanup', ['--status' => 'pending', '--before' => '14', '--dry-run' => true])
        ->assertSuccessful();

    expect(Transaction::find($old->id))->not->toBeNull();
});

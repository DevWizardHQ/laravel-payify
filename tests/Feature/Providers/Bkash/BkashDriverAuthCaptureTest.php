<?php

use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Events\PaymentCaptured;
use DevWizard\Payify\Events\PaymentVoided;
use DevWizard\Payify\Models\Transaction;
use DevWizard\Payify\Tests\Fixtures\FixtureLoader;
use GuzzleHttp\Handler\MockHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);
require_once __DIR__.'/../../../TestHelpers/bkash_driver_with.php';

it('captures an authorized transaction', function () {
    Event::fake([PaymentCaptured::class]);
    $mock = new MockHandler([FixtureLoader::json('Bkash/capture-success.json')]);
    $driver = bkashDriverWith($mock);

    $txn = Transaction::create([
        'provider' => 'bkash', 'reference' => 'AU-C', 'amount' => 100,
        'currency' => 'BDT', 'status' => TransactionStatus::Processing,
        'provider_transaction_id' => 'TR0011sandbox789',
        'intent' => 'authorization',
        'authorized_at' => now(),
    ]);

    $driver->capture($txn);

    expect($txn->fresh()->status)->toBe(TransactionStatus::Succeeded);
    expect($txn->fresh()->captured_at)->not->toBeNull();
    Event::assertDispatched(PaymentCaptured::class);
});

it('voids an authorized transaction', function () {
    Event::fake([PaymentVoided::class]);
    $mock = new MockHandler([FixtureLoader::json('Bkash/void-success.json')]);
    $driver = bkashDriverWith($mock);

    $txn = Transaction::create([
        'provider' => 'bkash', 'reference' => 'AU-V', 'amount' => 100,
        'currency' => 'BDT', 'status' => TransactionStatus::Processing,
        'provider_transaction_id' => 'TR0011sandbox789',
        'intent' => 'authorization',
    ]);

    $driver->void($txn);

    expect($txn->fresh()->status)->toBe(TransactionStatus::Cancelled);
    Event::assertDispatched(PaymentVoided::class);
});

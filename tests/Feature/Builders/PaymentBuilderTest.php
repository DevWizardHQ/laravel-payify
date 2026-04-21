<?php

use DevWizard\Payify\Builders\PaymentBuilder;
use DevWizard\Payify\Drivers\FakeDriver;
use DevWizard\Payify\Dto\PaymentResponse;
use DevWizard\Payify\Managers\PayifyManager;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('payify.default', 'fake');
    config()->set('payify.providers.fake', [
        'driver' => FakeDriver::class,
        'mode' => 'sandbox',
        'credentials' => [],
    ]);
});

it('returns a PaymentBuilder from driver()', function () {
    $manager = app(PayifyManager::class);
    expect($manager->driver('fake'))->toBeInstanceOf(PaymentBuilder::class);
});

it('builds and pays via fluent chain', function () {
    $response = app(PayifyManager::class)
        ->driver('fake')
        ->amount(250, 'BDT')
        ->invoice('INV-CHAIN')
        ->callback('https://app.test/cb')
        ->pay();

    expect($response)->toBeInstanceOf(PaymentResponse::class);
    expect(Transaction::where('reference', 'INV-CHAIN')->exists())->toBeTrue();
});

it('accepts array shortcut to terminal method', function () {
    $response = app(PayifyManager::class)
        ->driver('fake')
        ->pay([
            'amount' => 75,
            'currency' => 'BDT',
            'reference' => 'INV-ARR',
            'callback' => 'https://app.test/cb',
        ]);

    expect($response->amount)->toBe(75.0);
});

it('merges chain state with terminal array', function () {
    $response = app(PayifyManager::class)
        ->driver('fake')
        ->amount(500)
        ->pay([
            'reference' => 'INV-MIX',
            'currency' => 'BDT',
        ]);

    expect($response->amount)->toBe(500.0);
    expect(Transaction::where('reference', 'INV-MIX')->exists())->toBeTrue();
});

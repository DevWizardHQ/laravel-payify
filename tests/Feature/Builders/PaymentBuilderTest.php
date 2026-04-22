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

it('sets intent via fluent chain', function () {
    app(PayifyManager::class)->driver('fake')
        ->amount(100)->invoice('INT-1')
        ->intent('authorization')->pay();

    $txn = Transaction::where('reference', 'INT-1')->first();
    expect($txn->intent)->toBe('authorization');
});

it('sets address via fluent chain', function () {
    app(PayifyManager::class)->driver('fake')
        ->amount(10)->invoice('ADDR-1')
        ->address(line1: '1 Main', line2: null, city: 'Dhaka', state: null, postcode: '1000', country: 'BD')
        ->pay();

    $txn = Transaction::where('reference', 'ADDR-1')->first();
    expect($txn->customer['address1'])->toBe('1 Main');
    expect($txn->customer['city'])->toBe('Dhaka');
});

it('sets productCategory/productName/productProfile', function () {
    app(PayifyManager::class)->driver('fake')
        ->amount(10)->invoice('PRD-1')
        ->productCategory('books')
        ->productName('X')
        ->productProfile('general')
        ->pay();

    $txn = Transaction::where('reference', 'PRD-1')->first();
    expect($txn->request_payload['product_category'])->toBe('books');
});

it('sets gateway override', function () {
    app(PayifyManager::class)->driver('fake')
        ->amount(10)->invoice('GW-1')
        ->gateway('visacard')->pay();

    $txn = Transaction::where('reference', 'GW-1')->first();
    expect($txn->request_payload['gateway'])->toBe('visacard');
});

it('sets EMI options', function () {
    app(PayifyManager::class)->driver('fake')
        ->amount(10)->invoice('EMI-1')
        ->emi(true, 12)->pay();

    $txn = Transaction::where('reference', 'EMI-1')->first();
    expect($txn->request_payload['emi_option'])->toBe('1');
    expect($txn->request_payload['emi_max_installments'])->toBe(12);
});

it('sets line items', function () {
    app(PayifyManager::class)->driver('fake')
        ->amount(10)->invoice('LI-1')
        ->lineItems([['name' => 'Book', 'price' => 5, 'quantity' => 2]])
        ->pay();

    $txn = Transaction::where('reference', 'LI-1')->first();
    expect($txn->request_payload['line_items'])->toHaveCount(1);
});

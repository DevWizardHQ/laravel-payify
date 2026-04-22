<?php

use DevWizard\Payify\Dto\Customer;
use DevWizard\Payify\Dto\LineItem;
use DevWizard\Payify\Dto\PaymentRequest;

it('stores all fields', function () {
    $req = new PaymentRequest(
        amount: 100.50,
        currency: 'BDT',
        reference: 'INV-1',
        customer: new Customer(name: 'Iqbal'),
        callbackUrl: 'https://app.test/cb',
        webhookUrl: 'https://app.test/wh',
        mode: 'tokenized',
        payable: null,
        metadata: ['cart' => 1],
        extras: ['payerReference' => '017'],
    );

    expect($req->amount)->toBe(100.50);
    expect($req->currency)->toBe('BDT');
    expect($req->reference)->toBe('INV-1');
    expect($req->customer->name)->toBe('Iqbal');
    expect($req->mode)->toBe('tokenized');
    expect($req->metadata)->toBe(['cart' => 1]);
    expect($req->extras)->toBe(['payerReference' => '017']);
});

it('builds from array with nested customer', function () {
    $req = PaymentRequest::fromArray([
        'amount' => 500,
        'currency' => 'BDT',
        'reference' => 'INV-2',
        'customer' => ['name' => 'X', 'email' => 'x@y.z'],
        'callback' => 'https://cb',
        'webhook' => 'https://wh',
    ]);

    expect($req->amount)->toBe(500.0);
    expect($req->customer)->toBeInstanceOf(Customer::class);
    expect($req->customer->name)->toBe('X');
    expect($req->callbackUrl)->toBe('https://cb');
    expect($req->webhookUrl)->toBe('https://wh');
});

it('accepts already-built Customer', function () {
    $c = new Customer(name: 'Direct');
    $req = PaymentRequest::fromArray([
        'amount' => 1,
        'currency' => 'BDT',
        'reference' => 'r',
        'customer' => $c,
    ]);
    expect($req->customer)->toBe($c);
});

it('defaults optional fields', function () {
    $req = PaymentRequest::fromArray([
        'amount' => 10,
        'currency' => 'BDT',
        'reference' => 'r',
    ]);
    expect($req->customer)->toBeNull();
    expect($req->callbackUrl)->toBeNull();
    expect($req->mode)->toBeNull();
    expect($req->metadata)->toBe([]);
    expect($req->extras)->toBe([]);
});

it('exports to array', function () {
    $req = new PaymentRequest(amount: 10, currency: 'BDT', reference: 'r');
    $out = $req->toArray();
    expect($out['amount'])->toBe(10.0);
    expect($out['currency'])->toBe('BDT');
    expect($out['reference'])->toBe('r');
    expect($out['customer'])->toBeNull();
});

it('supports phase 2 fields', function () {
    $req = new PaymentRequest(
        amount: 100, currency: 'BDT', reference: 'r',
        intent: 'authorization',
        productCategory: 'books',
        productName: 'Laravel Guide',
        productProfile: 'general',
        gateway: 'visacard',
        emiOption: '1',
        emiMaxInstallments: 12,
        lineItems: [new LineItem(name: 'Book', price: 100)],
    );

    expect($req->intent)->toBe('authorization');
    expect($req->productCategory)->toBe('books');
    expect($req->gateway)->toBe('visacard');
    expect($req->emiOption)->toBe('1');
    expect($req->emiMaxInstallments)->toBe(12);
    expect($req->lineItems)->toHaveCount(1);
});

it('fromArray parses lineItems from assoc arrays', function () {
    $req = PaymentRequest::fromArray([
        'amount' => 10, 'currency' => 'BDT', 'reference' => 'r',
        'lineItems' => [
            ['name' => 'X', 'price' => 5, 'quantity' => 2],
        ],
    ]);
    expect($req->lineItems)->toHaveCount(1);
    expect($req->lineItems[0])->toBeInstanceOf(LineItem::class);
    expect($req->lineItems[0]->price)->toBe(5.0);
});

it('fromArray accepts line_items snake_case alias', function () {
    $req = PaymentRequest::fromArray([
        'amount' => 10, 'currency' => 'BDT', 'reference' => 'r',
        'line_items' => [['name' => 'Y', 'price' => 2]],
    ]);
    expect($req->lineItems)->toHaveCount(1);
});

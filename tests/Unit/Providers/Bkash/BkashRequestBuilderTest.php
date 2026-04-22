<?php

use DevWizard\Payify\Dto\Customer;
use DevWizard\Payify\Dto\PaymentRequest;
use DevWizard\Payify\Providers\Bkash\BkashRequestBuilder;

it('sanitizes angle brackets, ampersand, and trims to 255 chars', function () {
    $builder = new BkashRequestBuilder;

    expect($builder->sanitize('<script>&alert'))->toBe('scriptalert');
    expect(strlen($builder->sanitize(str_repeat('a', 300))))->toBe(255);
});

it('formats amount as 2-decimal string', function () {
    $builder = new BkashRequestBuilder;

    expect($builder->formatAmount(100))->toBe('100.00');
    expect($builder->formatAmount(100.5))->toBe('100.50');
    expect($builder->formatAmount(100.505))->toBe('100.51');
});

it('builds TLV format for merchant info', function () {
    $builder = new BkashRequestBuilder;

    $tlv = $builder->tlv(['MI' => 'MID54', 'RF' => '123456789']);

    expect($tlv)->toBe('MI05MID54RF09123456789');
});

it('builds create payload with defaults for checkout mode', function () {
    $builder = new BkashRequestBuilder;

    $req = new PaymentRequest(
        amount: 100.50,
        currency: 'BDT',
        reference: 'INV-1',
        customer: new Customer(phone: '01700000000'),
        callbackUrl: 'https://app.test/cb',
    );

    $payload = $builder->buildCreate($req);

    expect($payload['mode'])->toBe('0011');
    expect($payload['payerReference'])->toBe('01700000000');
    expect($payload['callbackURL'])->toBe('https://app.test/cb');
    expect($payload['amount'])->toBe('100.50');
    expect($payload['currency'])->toBe('BDT');
    expect($payload['intent'])->toBe('sale');
    expect($payload['merchantInvoiceNumber'])->toBe('INV-1');
});

it('uses authorization intent when request sets it', function () {
    $builder = new BkashRequestBuilder;

    $req = new PaymentRequest(
        amount: 100, currency: 'BDT', reference: 'INV-A',
        customer: new Customer(phone: '017'),
        callbackUrl: 'https://cb',
        intent: 'authorization',
    );

    expect($builder->buildCreate($req)['intent'])->toBe('authorization');
});

it('includes agreementID when provided', function () {
    $builder = new BkashRequestBuilder;

    $req = new PaymentRequest(
        amount: 10, currency: 'BDT', reference: 'R',
        customer: new Customer(phone: '017'),
        callbackUrl: 'https://cb',
    );

    $payload = $builder->buildCreate($req, agreementId: 'AGR-123');
    expect($payload['mode'])->toBe('0001');
    expect($payload['agreementID'])->toBe('AGR-123');
});

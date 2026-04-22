<?php

use DevWizard\Payify\Dto\Customer;
use DevWizard\Payify\Dto\LineItem;
use DevWizard\Payify\Dto\PaymentRequest;
use DevWizard\Payify\Providers\Sslcommerz\SslcommerzPayloadBuilder;

it('builds init payload with mandatory fields', function () {
    $builder = new SslcommerzPayloadBuilder([
        'credentials' => ['store_id' => 'S1', 'store_passwd' => 'P1'],
        'defaults' => ['product_category' => 'General', 'product_profile' => 'general'],
    ]);

    $req = new PaymentRequest(
        amount: 1000,
        currency: 'BDT',
        reference: 'INV-SSL-1',
        customer: new Customer(
            name: 'Iqbal', email: 'a@b.com', phone: '017',
            address1: '1 Main', city: 'Dhaka', country: 'BD',
        ),
        callbackUrl: 'https://app.test/callback/sslcommerz/success',
    );

    $payload = $builder->build($req);

    expect($payload['store_id'])->toBe('S1');
    expect($payload['store_passwd'])->toBe('P1');
    expect($payload['total_amount'])->toBe('1000.00');
    expect($payload['currency'])->toBe('BDT');
    expect($payload['tran_id'])->toBe('INV-SSL-1');
    expect($payload['product_category'])->toBe('General');
    expect($payload['cus_name'])->toBe('Iqbal');
    expect($payload['cus_email'])->toBe('a@b.com');
    expect($payload['cus_phone'])->toBe('017');
    expect($payload['cus_add1'])->toBe('1 Main');
    expect($payload['cus_city'])->toBe('Dhaka');
    expect($payload['cus_country'])->toBe('BD');
    expect($payload['success_url'])->toBe('https://app.test/callback/sslcommerz/success');
});

it('serializes line items as cart JSON', function () {
    $builder = new SslcommerzPayloadBuilder(['credentials' => ['store_id' => 'S', 'store_passwd' => 'P'], 'defaults' => []]);

    $req = new PaymentRequest(
        amount: 100, currency: 'BDT', reference: 'R',
        customer: new Customer(name: 'X', email: 'x@y.z', phone: '0'),
        callbackUrl: 'https://cb',
        lineItems: [
            new LineItem(name: 'Book', price: 25, quantity: 2),
            new LineItem(name: 'Pen', price: 50, quantity: 1),
        ],
    );

    $payload = $builder->build($req);

    expect($payload)->toHaveKey('cart');
    $cart = json_decode($payload['cart'], true);
    expect($cart)->toHaveCount(2);
    expect($cart[0])->toMatchArray(['product' => 'Book', 'amount' => '50.00']);
});

it('injects EMI fields', function () {
    $builder = new SslcommerzPayloadBuilder(['credentials' => ['store_id' => 'S', 'store_passwd' => 'P'], 'defaults' => []]);
    $req = new PaymentRequest(
        amount: 1000, currency: 'BDT', reference: 'E',
        customer: new Customer(name: 'X', email: 'x@y.z', phone: '0'),
        callbackUrl: 'https://cb',
        emiOption: '1', emiMaxInstallments: 12,
    );

    $payload = $builder->build($req);

    expect($payload['emi_option'])->toBe('1');
    expect($payload['emi_max_inst_option'])->toBe(12);
});

it('maps value_a/b/c/d from extras', function () {
    $builder = new SslcommerzPayloadBuilder(['credentials' => ['store_id' => 'S', 'store_passwd' => 'P'], 'defaults' => []]);
    $req = new PaymentRequest(
        amount: 10, currency: 'BDT', reference: 'V',
        customer: new Customer(name: 'X', email: 'x@y.z', phone: '0'),
        callbackUrl: 'https://cb',
        extras: ['value_a' => 'alpha', 'value_b' => 'beta'],
    );

    $payload = $builder->build($req);

    expect($payload['value_a'])->toBe('alpha');
    expect($payload['value_b'])->toBe('beta');
});

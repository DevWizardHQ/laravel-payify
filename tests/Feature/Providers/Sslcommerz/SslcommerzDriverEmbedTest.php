<?php

use DevWizard\Payify\Dto\Customer;
use DevWizard\Payify\Dto\PaymentRequest;
use GuzzleHttp\Handler\MockHandler;

require_once __DIR__.'/../../../TestHelpers/sslcommerz_driver_with.php';

it('returns sandbox embed script URL', function () {
    $driver = sslcommerzDriverWith(new MockHandler([]));

    expect($driver->embedScript())->toBe('https://sandbox.sslcommerz.com/embed.min.js?0.0.1');
});

it('builds embedAttributes with postdata JSON', function () {
    $driver = sslcommerzDriverWith(new MockHandler([]));

    $attrs = $driver->embedAttributes(new PaymentRequest(
        amount: 100, currency: 'BDT', reference: 'EMB-1',
        customer: new Customer(name: 'X', email: 'x@y.z', phone: '0', city: 'Dhaka', country: 'BD'),
        callbackUrl: 'https://cb',
    ));

    expect($attrs['data-sslcommerz'])->toBe('checkout');
    $postdata = json_decode($attrs['data-postdata'], true);
    expect($postdata['tran_id'])->toBe('EMB-1');
});

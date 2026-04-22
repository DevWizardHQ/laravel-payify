<?php

use GuzzleHttp\Handler\MockHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);
require_once __DIR__.'/../../../TestHelpers/sslcommerz_driver_with.php';

it('verifies a trusted IPN (all layers disabled) and returns payload', function () {
    $driver = sslcommerzDriverWith(new MockHandler([]));

    $request = Request::create('/w', 'POST', [
        'tran_id' => 'INV-SSL-1',
        'bank_tran_id' => 'BT-1',
        'amount' => '1000.00',
        'currency' => 'BDT',
        'status' => 'VALID',
    ]);

    $payload = $driver->verifyWebhook($request);

    expect($payload->provider)->toBe('sslcommerz');
    expect($payload->event)->toBe('payment.succeeded');
    expect($payload->verified)->toBeTrue();
});

<?php

use DevWizard\Payify\Dto\PayoutRequest;

it('stores payout fields', function () {
    $r = new PayoutRequest(
        reference: 'PAYOUT-1',
        amount: 5000.0,
        currency: 'BDT',
        receiverIdentifier: '01700000000',
        receiverName: 'Vendor',
        reason: 'monthly payout',
        extras: ['note' => 'x'],
    );

    expect($r->reference)->toBe('PAYOUT-1');
    expect($r->amount)->toBe(5000.0);
    expect($r->receiverIdentifier)->toBe('01700000000');
    expect($r->reason)->toBe('monthly payout');
});

it('builds from array', function () {
    $r = PayoutRequest::fromArray([
        'reference' => 'PAYOUT-2',
        'amount' => 100,
        'currency' => 'BDT',
        'receiverIdentifier' => '017',
    ]);
    expect($r->amount)->toBe(100.0);
    expect($r->receiverName)->toBeNull();
});

<?php

use DevWizard\Payify\Dto\PayoutResponse;
use DevWizard\Payify\Enums\TransactionStatus;

it('stores payout response fields', function () {
    $r = new PayoutResponse(
        transactionId: 'uuid',
        providerPayoutId: 'po_1',
        status: TransactionStatus::Succeeded,
        amount: 5000.0,
        currency: 'BDT',
    );

    expect($r->transactionId)->toBe('uuid');
    expect($r->providerPayoutId)->toBe('po_1');
    expect($r->status)->toBe(TransactionStatus::Succeeded);
});

it('allows failure response with error', function () {
    $r = new PayoutResponse(
        transactionId: 'uuid', providerPayoutId: null,
        status: TransactionStatus::Failed,
        amount: 0, currency: 'BDT',
        errorCode: 'E', errorMessage: 'oops',
    );
    expect($r->errorCode)->toBe('E');
});

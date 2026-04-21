<?php

use DevWizard\Payify\Dto\PaymentResponse;
use DevWizard\Payify\Enums\TransactionStatus;

it('stores response fields', function () {
    $r = new PaymentResponse(
        transactionId: 'uuid-1',
        providerTransactionId: 'pay_123',
        status: TransactionStatus::Processing,
        amount: 100.0,
        currency: 'BDT',
        redirectUrl: 'https://gateway/pay',
    );

    expect($r->transactionId)->toBe('uuid-1');
    expect($r->status)->toBe(TransactionStatus::Processing);
    expect($r->redirectUrl)->toBe('https://gateway/pay');
});

it('identifies success and failure', function () {
    $ok = new PaymentResponse(
        transactionId: 't', providerTransactionId: null,
        status: TransactionStatus::Succeeded, amount: 1, currency: 'BDT',
    );
    $fail = new PaymentResponse(
        transactionId: 't', providerTransactionId: null,
        status: TransactionStatus::Failed, amount: 1, currency: 'BDT',
        errorCode: 'X', errorMessage: 'nope',
    );

    expect($ok->succeeded())->toBeTrue();
    expect($ok->failed())->toBeFalse();
    expect($fail->failed())->toBeTrue();
    expect($fail->succeeded())->toBeFalse();
});

it('detects redirect requirement', function () {
    $r = new PaymentResponse(
        transactionId: 't', providerTransactionId: null,
        status: TransactionStatus::Processing, amount: 1, currency: 'BDT',
        redirectUrl: 'https://x',
    );
    expect($r->requiresRedirect())->toBeTrue();
});

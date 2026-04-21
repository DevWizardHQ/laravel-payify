<?php

use DevWizard\Payify\Dto\StatusResponse;
use DevWizard\Payify\Enums\TransactionStatus;

it('stores status fields', function () {
    $r = new StatusResponse(
        transactionId: 't-1',
        status: TransactionStatus::Succeeded,
        providerTransactionId: 'pay_1',
        paidAmount: 100.0,
        refundedAmount: 0.0,
    );

    expect($r->transactionId)->toBe('t-1');
    expect($r->status)->toBe(TransactionStatus::Succeeded);
    expect($r->paidAmount)->toBe(100.0);
});

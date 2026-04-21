<?php

use DevWizard\Payify\Dto\RefundRequest;
use DevWizard\Payify\Dto\RefundResponse;
use DevWizard\Payify\Dto\WebhookPayload;
use DevWizard\Payify\Enums\TransactionStatus;

it('builds refund request from array', function () {
    $r = RefundRequest::fromArray([
        'transactionId' => 't-1',
        'amount' => 50,
        'reason' => 'refund',
    ]);

    expect($r->transactionId)->toBe('t-1');
    expect($r->amount)->toBe(50.0);
    expect($r->reason)->toBe('refund');
});

it('allows null amount for full refund', function () {
    $r = RefundRequest::fromArray(['transactionId' => 't']);
    expect($r->amount)->toBeNull();
});

it('constructs refund response', function () {
    $r = new RefundResponse(
        transactionId: 't-1', refundId: 'r-1', amount: 50.0,
        status: TransactionStatus::Refunded,
    );
    expect($r->status)->toBe(TransactionStatus::Refunded);
    expect($r->amount)->toBe(50.0);
});

it('builds refund response from webhook payload', function () {
    $wh = new WebhookPayload(
        provider: 'bkash', event: 'payment.refunded',
        providerTransactionId: 'pay_1', reference: 'INV-1',
        amount: 25.0, currency: 'BDT',
        raw: ['refund_id' => 'rf_1'], verified: true,
    );

    $r = RefundResponse::fromWebhook($wh);
    expect($r->refundId)->toBe('rf_1');
    expect($r->amount)->toBe(25.0);
    expect($r->status)->toBe(TransactionStatus::Refunded);
});

<?php

use DevWizard\Payify\Dto\WebhookPayload;

it('stores webhook payload fields', function () {
    $p = new WebhookPayload(
        provider: 'bkash',
        event: 'payment.succeeded',
        providerTransactionId: 'pay_1',
        reference: 'INV-1',
        amount: 100.0,
        currency: 'BDT',
        raw: ['foo' => 'bar'],
        verified: true,
    );

    expect($p->provider)->toBe('bkash');
    expect($p->event)->toBe('payment.succeeded');
    expect($p->verified)->toBeTrue();
    expect($p->raw)->toBe(['foo' => 'bar']);
});

it('supports unverified webhooks', function () {
    $p = new WebhookPayload(
        provider: 'x', event: 'e', providerTransactionId: null,
        reference: null, amount: null, currency: null,
        raw: [], verified: false,
    );
    expect($p->verified)->toBeFalse();
});

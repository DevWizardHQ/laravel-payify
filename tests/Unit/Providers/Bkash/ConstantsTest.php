<?php

use DevWizard\Payify\Providers\Bkash\Constants;

it('exposes endpoint paths', function () {
    expect(Constants::PATH_GRANT)->toBe('/tokenized/checkout/token/grant');
    expect(Constants::PATH_REFRESH)->toBe('/tokenized/checkout/token/refresh');
    expect(Constants::PATH_CREATE)->toBe('/tokenized/checkout/create');
    expect(Constants::PATH_EXECUTE)->toBe('/tokenized/checkout/execute');
    expect(Constants::PATH_STATUS)->toBe('/tokenized/checkout/payment/status');
    expect(Constants::PATH_SEARCH)->toBe('/tokenized/checkout/general/searchTransaction');
    expect(Constants::PATH_REFUND)->toBe('/tokenized/checkout/payment/refund');
    expect(Constants::PATH_CAPTURE)->toBe('/tokenized/checkout/payment/confirm/capture');
    expect(Constants::PATH_VOID)->toBe('/tokenized/checkout/payment/confirm/void');
    expect(Constants::PATH_AGREEMENT_CANCEL)->toBe('/tokenized/checkout/agreement/cancel');
    expect(Constants::PATH_PAYOUT_INIT)->toBe('/tokenized/payout/initiate');
    expect(Constants::PATH_PAYOUT_EXECUTE)->toBe('/tokenized/payout/execute');
});

it('exposes mode codes', function () {
    expect(Constants::MODE_CHECKOUT)->toBe('0011');
    expect(Constants::MODE_AGREEMENT_CREATE)->toBe('0000');
    expect(Constants::MODE_AGREEMENT_PAY)->toBe('0001');
});

it('exposes success status code', function () {
    expect(Constants::STATUS_SUCCESS)->toBe('0000');
});

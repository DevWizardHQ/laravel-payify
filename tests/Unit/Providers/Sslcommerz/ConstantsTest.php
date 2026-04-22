<?php

use DevWizard\Payify\Providers\Sslcommerz\Constants;

it('exposes endpoint paths', function () {
    expect(Constants::PATH_INIT)->toBe('/gwprocess/v4/api.php');
    expect(Constants::PATH_VALIDATOR)->toBe('/validator/api/validationserverAPI.php');
    expect(Constants::PATH_TRANSACTION)->toBe('/validator/api/merchantTransIDvalidationAPI.php');
    expect(Constants::PATH_REFUND)->toBe('/validator/api/merchantTransIDvalidationAPI.php');
});

it('exposes status enums', function () {
    expect(Constants::STATUS_VALID)->toBe('VALID');
    expect(Constants::STATUS_VALIDATED)->toBe('VALIDATED');
    expect(Constants::STATUS_FAILED)->toBe('FAILED');
    expect(Constants::STATUS_CANCELLED)->toBe('CANCELLED');
    expect(Constants::API_CONNECT_DONE)->toBe('DONE');
});

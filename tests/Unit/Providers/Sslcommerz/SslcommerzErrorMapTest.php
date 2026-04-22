<?php

use DevWizard\Payify\Exceptions\InvalidCredentialsException;
use DevWizard\Payify\Exceptions\PaymentFailedException;
use DevWizard\Payify\Exceptions\ValidationException;
use DevWizard\Payify\Providers\Sslcommerz\SslcommerzErrorMap;

it('maps FAILED to PaymentFailedException', function () {
    expect(SslcommerzErrorMap::map('FAILED', 'declined'))->toBeInstanceOf(PaymentFailedException::class);
});

it('maps INACTIVE to InvalidCredentialsException', function () {
    expect(SslcommerzErrorMap::map('INACTIVE', 'ip not whitelisted'))->toBeInstanceOf(InvalidCredentialsException::class);
});

it('maps INVALID_REQUEST to ValidationException', function () {
    expect(SslcommerzErrorMap::map('INVALID_REQUEST', 'bad field'))->toBeInstanceOf(ValidationException::class);
});

it('maps unknown status to PaymentFailedException with raw code', function () {
    $e = SslcommerzErrorMap::map('MYSTERY', 'unknown');
    expect($e)->toBeInstanceOf(PaymentFailedException::class);
    expect($e->providerErrorCode())->toBe('MYSTERY');
});

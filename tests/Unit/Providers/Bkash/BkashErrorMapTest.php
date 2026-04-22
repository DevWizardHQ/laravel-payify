<?php

use DevWizard\Payify\Exceptions\AlreadyCompletedException;
use DevWizard\Payify\Exceptions\InvalidCredentialsException;
use DevWizard\Payify\Exceptions\PaymentFailedException;
use DevWizard\Payify\Exceptions\RefundFailedException;
use DevWizard\Payify\Exceptions\ValidationException;
use DevWizard\Payify\Providers\Bkash\BkashErrorMap;

it('maps 2079 to InvalidCredentialsException', function () {
    $e = BkashErrorMap::map('2079', 'Invalid app token');
    expect($e)->toBeInstanceOf(InvalidCredentialsException::class);
});

it('maps completed-state codes to AlreadyCompletedException', function () {
    foreach (['2062', '2068', '2116', '2117'] as $code) {
        expect(BkashErrorMap::map($code, 'already'))->toBeInstanceOf(AlreadyCompletedException::class);
    }
});

it('maps refund codes to RefundFailedException', function () {
    $e = BkashErrorMap::map('2071', 'window expired');
    expect($e)->toBeInstanceOf(RefundFailedException::class);
    expect($e->providerErrorCode())->toBe('2071');
});

it('maps validation codes to ValidationException', function () {
    $e = BkashErrorMap::map('2065', 'missing field');
    expect($e)->toBeInstanceOf(ValidationException::class);
});

it('maps merchant-permission codes to InvalidCredentialsException', function () {
    foreach (['2080', '2081', '2082'] as $code) {
        expect(BkashErrorMap::map($code, 'bad merchant'))->toBeInstanceOf(InvalidCredentialsException::class);
    }
});

it('falls back to PaymentFailedException for unknown codes', function () {
    $e = BkashErrorMap::map('9999', 'mystery');
    expect($e)->toBeInstanceOf(PaymentFailedException::class);
    expect($e->providerErrorCode())->toBe('9999');
    expect($e->providerErrorMessage())->toBe('mystery');
});

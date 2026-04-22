<?php

use DevWizard\Payify\Exceptions\AlreadyCompletedException;
use DevWizard\Payify\Exceptions\InvalidCredentialsException;
use DevWizard\Payify\Exceptions\IpNotAllowedException;
use DevWizard\Payify\Exceptions\PayifyException;
use DevWizard\Payify\Exceptions\PaymentFailedException;
use DevWizard\Payify\Exceptions\ProviderNotFoundException;
use DevWizard\Payify\Exceptions\RefundFailedException;
use DevWizard\Payify\Exceptions\UnsupportedOperationException;
use DevWizard\Payify\Exceptions\ValidationException;
use DevWizard\Payify\Exceptions\WebhookVerificationException;

it('all exceptions extend the base', function () {
    foreach ([
        ProviderNotFoundException::class,
        InvalidCredentialsException::class,
        PaymentFailedException::class,
        RefundFailedException::class,
        WebhookVerificationException::class,
        UnsupportedOperationException::class,
        ValidationException::class,
    ] as $cls) {
        $instance = new $cls('x');
        expect($instance)->toBeInstanceOf(PayifyException::class);
        expect($instance)->toBeInstanceOf(Throwable::class);
    }
});

it('payment failed carries provider code', function () {
    $e = new PaymentFailedException('boom', code: 0);
    $e->setProviderError('E_TIMEOUT', 'timeout');

    expect($e->providerErrorCode())->toBe('E_TIMEOUT');
    expect($e->providerErrorMessage())->toBe('timeout');
});

it('webhook verification exception stores reason', function () {
    $e = new WebhookVerificationException('bad sig', reason: 'hash_mismatch');
    expect($e->reason())->toBe('hash_mismatch');
});

it('already completed exception extends base', function () {
    $e = new AlreadyCompletedException('already done');
    expect($e)->toBeInstanceOf(PayifyException::class);
    expect($e->getMessage())->toBe('already done');
});

it('ip not allowed exception extends webhook verification', function () {
    $e = new IpNotAllowedException('10.0.0.1 not allowed');
    expect($e)->toBeInstanceOf(WebhookVerificationException::class);
    expect($e->getMessage())->toBe('10.0.0.1 not allowed');
});

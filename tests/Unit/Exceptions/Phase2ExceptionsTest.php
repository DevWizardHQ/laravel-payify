<?php

use DevWizard\Payify\Exceptions\AlreadyCompletedException;
use DevWizard\Payify\Exceptions\IpNotAllowedException;
use DevWizard\Payify\Exceptions\PayifyException;
use DevWizard\Payify\Exceptions\WebhookVerificationException;

it('AlreadyCompletedException extends PayifyException', function () {
    $e = new AlreadyCompletedException('already done');
    expect($e)->toBeInstanceOf(PayifyException::class);
    expect($e->getMessage())->toBe('already done');
});

it('IpNotAllowedException extends WebhookVerificationException', function () {
    $e = new IpNotAllowedException('10.0.0.1 not allowed');
    expect($e)->toBeInstanceOf(WebhookVerificationException::class);
    expect($e->getMessage())->toBe('10.0.0.1 not allowed');
});

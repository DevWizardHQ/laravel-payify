<?php

use DevWizard\Payify\Enums\TransactionStatus;

it('exposes all lifecycle cases', function () {
    expect(TransactionStatus::Pending->value)->toBe('pending');
    expect(TransactionStatus::Processing->value)->toBe('processing');
    expect(TransactionStatus::Succeeded->value)->toBe('succeeded');
    expect(TransactionStatus::Failed->value)->toBe('failed');
    expect(TransactionStatus::Cancelled->value)->toBe('cancelled');
    expect(TransactionStatus::Refunded->value)->toBe('refunded');
    expect(TransactionStatus::PartiallyRefunded->value)->toBe('partially_refunded');
    expect(TransactionStatus::Expired->value)->toBe('expired');
});

it('classifies terminal states', function () {
    expect(TransactionStatus::Succeeded->isTerminal())->toBeTrue();
    expect(TransactionStatus::Failed->isTerminal())->toBeTrue();
    expect(TransactionStatus::Cancelled->isTerminal())->toBeTrue();
    expect(TransactionStatus::Refunded->isTerminal())->toBeTrue();
    expect(TransactionStatus::Pending->isTerminal())->toBeFalse();
    expect(TransactionStatus::Processing->isTerminal())->toBeFalse();
});

it('classifies refundable states', function () {
    expect(TransactionStatus::Succeeded->canRefund())->toBeTrue();
    expect(TransactionStatus::PartiallyRefunded->canRefund())->toBeTrue();
    expect(TransactionStatus::Pending->canRefund())->toBeFalse();
    expect(TransactionStatus::Failed->canRefund())->toBeFalse();
});

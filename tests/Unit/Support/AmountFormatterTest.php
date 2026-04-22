<?php

use DevWizard\Payify\Support\AmountFormatter;

it('converts BDT to paisa', function () {
    expect(AmountFormatter::toMinor(100.50, 'BDT'))->toBe(10050);
    expect(AmountFormatter::toMinor(0.01, 'BDT'))->toBe(1);
});

it('converts paisa to BDT', function () {
    expect(AmountFormatter::toMajor(10050, 'BDT'))->toBe(100.50);
});

it('converts USD to cents', function () {
    expect(AmountFormatter::toMinor(10.25, 'USD'))->toBe(1025);
});

it('handles zero-decimal currencies (JPY)', function () {
    expect(AmountFormatter::toMinor(1000, 'JPY'))->toBe(1000);
    expect(AmountFormatter::toMajor(1000, 'JPY'))->toBe(1000.0);
});

it('rounds half up when converting to minor units', function () {
    expect(AmountFormatter::toMinor(1.005, 'BDT'))->toBe(101);
});

<?php

use DevWizard\Payify\Support\ReferenceGenerator;

it('generates reference with prefix and expected length', function () {
    $ref = ReferenceGenerator::make(prefix: 'INV', length: 12);

    expect($ref)->toStartWith('INV-');
    expect(strlen($ref) - 4)->toBe(12);
});

it('generates unique references', function () {
    $refs = array_map(fn () => ReferenceGenerator::make(), range(1, 20));
    expect(count(array_unique($refs)))->toBe(20);
});

it('uses default prefix PAY', function () {
    expect(ReferenceGenerator::make())->toStartWith('PAY-');
});

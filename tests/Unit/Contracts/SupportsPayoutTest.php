<?php

use DevWizard\Payify\Contracts\SupportsPayout;

it('exposes SupportsPayout interface with expected methods', function () {
    expect(interface_exists(SupportsPayout::class))->toBeTrue();

    $reflection = new ReflectionClass(SupportsPayout::class);
    $methods = array_map(fn ($m) => $m->getName(), $reflection->getMethods());

    expect($methods)->toContain('initPayout', 'executePayout', 'payout');
});

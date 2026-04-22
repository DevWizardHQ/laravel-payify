<?php

use DevWizard\Payify\Contracts\SupportsEmi;

it('exposes SupportsEmi interface with expected methods', function () {
    expect(interface_exists(SupportsEmi::class))->toBeTrue();

    $reflection = new ReflectionClass(SupportsEmi::class);
    $methods = array_map(fn ($m) => $m->getName(), $reflection->getMethods());

    expect($methods)->toContain('emiOptions', 'buildEmiPayload');
});

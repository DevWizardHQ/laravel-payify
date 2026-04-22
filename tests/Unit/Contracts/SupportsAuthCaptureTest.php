<?php

use DevWizard\Payify\Contracts\SupportsAuthCapture;

it('exposes SupportsAuthCapture interface with expected methods', function () {
    expect(interface_exists(SupportsAuthCapture::class))->toBeTrue();

    $reflection = new ReflectionClass(SupportsAuthCapture::class);
    $methods = array_map(fn ($m) => $m->getName(), $reflection->getMethods());

    expect($methods)->toContain('authorize', 'capture', 'void');
});

<?php

use DevWizard\Payify\Contracts\SupportsEmbeddedCheckout;

it('exposes SupportsEmbeddedCheckout interface with expected methods', function () {
    expect(interface_exists(SupportsEmbeddedCheckout::class))->toBeTrue();

    $reflection = new ReflectionClass(SupportsEmbeddedCheckout::class);
    $methods = array_map(fn ($m) => $m->getName(), $reflection->getMethods());

    expect($methods)->toContain('embedScript', 'embedAttributes');
});

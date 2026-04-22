<?php

use DevWizard\Payify\Dto\TokenResponse;

it('stores token fields', function () {
    $t = new TokenResponse(
        token: 'tok_abc',
        last4: '4242',
        brand: 'VISA',
        expiresAt: '2030-12',
    );

    expect($t->token)->toBe('tok_abc');
    expect($t->last4)->toBe('4242');
    expect($t->brand)->toBe('VISA');
    expect($t->expiresAt)->toBe('2030-12');
});

it('allows minimal token', function () {
    $t = new TokenResponse(token: 'x');
    expect($t->last4)->toBeNull();
    expect($t->raw)->toBe([]);
});

<?php

use DevWizard\Payify\Drivers\FakeDriver;

it('lists configured providers with capabilities', function () {
    config()->set('payify.providers', [
        'fake' => [
            'driver' => FakeDriver::class,
            'mode' => 'sandbox',
            'credentials' => [],
        ],
    ]);

    $this->artisan('payify:list')
        ->expectsOutputToContain('fake')
        ->assertSuccessful();
});

it('warns when no providers configured', function () {
    config()->set('payify.providers', []);

    $this->artisan('payify:list')
        ->expectsOutputToContain('No providers configured')
        ->assertSuccessful();
});

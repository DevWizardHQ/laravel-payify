<?php

use DevWizard\Payify\Builders\PaymentBuilder;
use DevWizard\Payify\Drivers\FakeDriver;
use DevWizard\Payify\Facades\Payify;

beforeEach(function () {
    config()->set('payify.default', 'fake');
    config()->set('payify.providers.fake', [
        'driver' => FakeDriver::class,
        'mode' => 'sandbox',
        'credentials' => [],
    ]);
});

it('returns a builder via facade driver()', function () {
    expect(Payify::driver('fake'))->toBeInstanceOf(PaymentBuilder::class);
});

it('forwards default-driver calls via magic __call', function () {
    expect(Payify::driver())->toBeInstanceOf(PaymentBuilder::class);
});

afterEach(function () {
    \DevWizard\Payify\Payify::resetCustomRoutes();
});

it('registers custom routes and suppresses default registration', function () {
    \DevWizard\Payify\Payify::routes(['prefix' => 'custom/payments']);
    expect(\DevWizard\Payify\Payify::hasCustomRoutes())->toBeTrue();
    \DevWizard\Payify\Payify::resetCustomRoutes();
    expect(\DevWizard\Payify\Payify::hasCustomRoutes())->toBeFalse();
});

it('returns PayifyFake from facade fake()', function () {
    expect(\DevWizard\Payify\Facades\Payify::fake())->toBeInstanceOf(\DevWizard\Payify\Testing\PayifyFake::class);
});

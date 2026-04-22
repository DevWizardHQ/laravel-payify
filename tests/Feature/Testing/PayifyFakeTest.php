<?php

use DevWizard\Payify\Drivers\FakeDriver;
use DevWizard\Payify\Facades\Payify;
use DevWizard\Payify\Testing\PayifyFake;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('payify.default', 'fake');
    config()->set('payify.providers.fake', [
        'driver' => FakeDriver::class,
        'mode' => 'sandbox',
        'credentials' => [],
    ]);
});

it('installs PayifyFake via facade', function () {
    $fake = Payify::fake();
    expect($fake)->toBeInstanceOf(PayifyFake::class);
});

it('records pay() invocations for assertion', function () {
    $fake = Payify::fake([
        'fake' => [
            'pay' => ['status' => 'succeeded'],
        ],
    ]);

    Payify::driver('fake')
        ->amount(250, 'BDT')
        ->invoice('INV-ASSERT')
        ->pay();

    $fake->assertPaidCount(1);
    $fake->assertPaid(fn ($txn) => $txn->reference === 'INV-ASSERT');
});

it('asserts nothing paid when no calls made', function () {
    $fake = Payify::fake();

    $fake->assertNothingPaid();
});

it('supports canned responses per provider', function () {
    $fake = Payify::fake([
        'fake' => [
            'pay' => ['redirect_url' => 'https://cannery.test/x'],
        ],
    ]);

    $response = Payify::driver('fake')
        ->amount(10, 'BDT')
        ->invoice('INV-CAN')
        ->pay();

    expect($response->redirectUrl)->toBe('https://cannery.test/x');
});

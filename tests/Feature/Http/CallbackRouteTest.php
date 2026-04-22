<?php

use DevWizard\Payify\Drivers\FakeDriver;
use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('payify.default', 'fake');
    config()->set('payify.providers.fake', [
        'driver' => FakeDriver::class,
        'mode' => 'sandbox',
        'credentials' => [],
    ]);
    config()->set('payify.routes.middleware', []);
});

it('returns JSON status for callback without configured redirect', function () {
    Transaction::create([
        'provider' => 'fake', 'reference' => 'INV-CB', 'amount' => 100,
        'currency' => 'BDT', 'status' => TransactionStatus::Succeeded,
    ]);

    $this->getJson('/payify/callback/fake/success?reference=INV-CB')
        ->assertOk()
        ->assertJsonPath('status', 'succeeded');
});

it('redirects to configured url with status + transaction', function () {
    Transaction::create([
        'provider' => 'fake', 'reference' => 'INV-CB2', 'amount' => 50,
        'currency' => 'BDT', 'status' => TransactionStatus::Succeeded,
    ]);

    config()->set('payify.callback.redirect_url', 'https://host.test/checkout');

    $this->get('/payify/callback/fake/success?reference=INV-CB2')
        ->assertRedirect();
});

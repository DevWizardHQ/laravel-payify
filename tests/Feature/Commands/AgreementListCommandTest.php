<?php

use DevWizard\Payify\Models\Agreement;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists agreements', function () {
    Agreement::create(['provider' => 'bkash', 'agreement_id' => 'AGR-1', 'payer_reference' => '017', 'status' => 'active']);
    Agreement::create(['provider' => 'bkash', 'agreement_id' => 'AGR-2', 'payer_reference' => '018', 'status' => 'cancelled']);

    $this->artisan('payify:agreement:list')
        ->expectsOutputToContain('AGR-1')
        ->expectsOutputToContain('AGR-2')
        ->assertSuccessful();
});

it('filters by status', function () {
    Agreement::create(['provider' => 'bkash', 'agreement_id' => 'AGR-3', 'payer_reference' => '017', 'status' => 'active']);
    Agreement::create(['provider' => 'bkash', 'agreement_id' => 'AGR-4', 'payer_reference' => '018', 'status' => 'cancelled']);

    $this->artisan('payify:agreement:list', ['--status' => 'active'])
        ->expectsOutputToContain('AGR-3')
        ->doesntExpectOutputToContain('AGR-4')
        ->assertSuccessful();
});

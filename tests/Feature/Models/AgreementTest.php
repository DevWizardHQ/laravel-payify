<?php

use DevWizard\Payify\Models\Agreement;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('persists an agreement row', function () {
    $a = Agreement::create([
        'provider' => 'bkash',
        'agreement_id' => 'AGR-1',
        'payer_reference' => '01700000000',
        'status' => 'active',
        'activated_at' => now(),
    ]);

    expect($a->id)->toBeString();
    expect(strlen($a->id))->toBe(36);
    expect($a->isActive())->toBeTrue();
});

it('enforces unique (provider, agreement_id)', function () {
    Agreement::create([
        'provider' => 'bkash', 'agreement_id' => 'AGR-X',
        'payer_reference' => '017', 'status' => 'active',
    ]);

    expect(fn () => Agreement::create([
        'provider' => 'bkash', 'agreement_id' => 'AGR-X',
        'payer_reference' => '018', 'status' => 'active',
    ]))->toThrow(QueryException::class);
});

it('soft deletes agreements', function () {
    $a = Agreement::create([
        'provider' => 'bkash', 'agreement_id' => 'AGR-SD',
        'payer_reference' => '019', 'status' => 'cancelled',
    ]);
    $a->delete();

    expect(Agreement::find($a->id))->toBeNull();
    expect(Agreement::withTrashed()->find($a->id))->not->toBeNull();
});

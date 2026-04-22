<?php

use DevWizard\Payify\Contracts\SupportsRefundQuery;
use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('fails when no refund_ref_id is stored', function () {
    $t = Transaction::create([
        'provider' => 'fake', 'reference' => 'NOREF', 'amount' => 100,
        'currency' => 'BDT', 'status' => TransactionStatus::Succeeded,
    ]);

    $this->artisan('payify:refund:status', ['transaction_id' => $t->id])
        ->assertFailed();
});

it('RefundStatusCommand uses SupportsRefundQuery contract not method_exists', function () {
    $source = file_get_contents(dirname(__DIR__, 3).'/src/Commands/RefundStatusCommand.php');
    expect($source)->toContain('SupportsRefundQuery');
    expect($source)->not->toContain('method_exists');
});

it('SslcommerzDriver implements SupportsRefundQuery', function () {
    expect(\DevWizard\Payify\Providers\Sslcommerz\SslcommerzDriver::class)
        ->toImplement(SupportsRefundQuery::class);
});

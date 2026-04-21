<?php

use DevWizard\Payify\Dto\RefundResponse;
use DevWizard\Payify\Dto\StatusResponse;
use DevWizard\Payify\Dto\WebhookPayload;
use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Events\PaymentCancelled;
use DevWizard\Payify\Events\PaymentFailed;
use DevWizard\Payify\Events\PaymentInitiated;
use DevWizard\Payify\Events\PaymentRefunded;
use DevWizard\Payify\Events\PaymentStatusChecked;
use DevWizard\Payify\Events\PaymentSucceeded;
use DevWizard\Payify\Events\WebhookReceived;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeTxn(): Transaction
{
    return Transaction::create([
        'provider' => 'fake', 'reference' => 'ev-'.uniqid(),
        'amount' => 10, 'currency' => 'BDT',
        'status' => TransactionStatus::Pending,
    ]);
}

it('constructs each event with expected payload', function () {
    $t = makeTxn();

    expect((new PaymentInitiated($t))->transaction->id)->toBe($t->id);
    expect((new PaymentSucceeded($t))->transaction->id)->toBe($t->id);
    expect((new PaymentCancelled($t))->transaction->id)->toBe($t->id);

    $failed = new PaymentFailed($t, 'E_X', 'nope');
    expect($failed->errorCode)->toBe('E_X');
    expect($failed->errorMessage)->toBe('nope');

    $refund = new RefundResponse(
        transactionId: $t->id, refundId: 'r1', amount: 5.0,
        status: TransactionStatus::Refunded,
    );
    expect((new PaymentRefunded($t, $refund))->refund->refundId)->toBe('r1');

    $status = new StatusResponse(
        transactionId: $t->id, status: TransactionStatus::Succeeded,
    );
    expect((new PaymentStatusChecked($t, $status))->status->status)->toBe(TransactionStatus::Succeeded);

    $wh = new WebhookPayload(
        provider: 'fake', event: 'payment.succeeded',
        providerTransactionId: 'p1', reference: 'r', amount: 10,
        currency: 'BDT', raw: [], verified: true,
    );
    $wr = new WebhookReceived($wh, $t);
    expect($wr->payload->verified)->toBeTrue();
    expect($wr->transaction?->id)->toBe($t->id);
});

<?php

namespace DevWizard\Payify\Tests\Fixtures;

use DevWizard\Payify\Contracts\SupportsHostedCheckout;
use DevWizard\Payify\Drivers\AbstractDriver;
use DevWizard\Payify\Dto\PaymentRequest;
use DevWizard\Payify\Dto\PaymentResponse;
use DevWizard\Payify\Dto\StatusResponse;
use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Http\Request;

class NonWebhookDriver extends AbstractDriver implements SupportsHostedCheckout
{
    public function name(): string
    {
        return 'plain';
    }

    public function capabilities(): array
    {
        return [
            'refund' => false, 'tokenization' => false,
            'hosted_checkout' => true, 'direct_api' => false,
            'webhook' => false, 'partial_refund' => false,
            'currencies' => ['BDT'],
        ];
    }

    public function handleCallback(Request $request): PaymentResponse
    {
        return new PaymentResponse(
            transactionId: '', providerTransactionId: null,
            status: TransactionStatus::Pending, amount: 0, currency: 'BDT',
        );
    }

    protected function executePayment(PaymentRequest $req, Transaction $txn): PaymentResponse
    {
        return new PaymentResponse(
            transactionId: $txn->id, providerTransactionId: null,
            status: TransactionStatus::Processing, amount: $req->amount, currency: $req->currency,
        );
    }

    protected function executeStatus(Transaction $txn): StatusResponse
    {
        return new StatusResponse(transactionId: $txn->id, status: $txn->status);
    }
}

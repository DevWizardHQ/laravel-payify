<?php

namespace DevWizard\Payify\Drivers;

use DevWizard\Payify\Contracts\HandlesWebhook;
use DevWizard\Payify\Contracts\SupportsHostedCheckout;
use DevWizard\Payify\Contracts\SupportsRefund;
use DevWizard\Payify\Dto\PaymentRequest;
use DevWizard\Payify\Dto\PaymentResponse;
use DevWizard\Payify\Dto\RefundRequest;
use DevWizard\Payify\Dto\RefundResponse;
use DevWizard\Payify\Dto\StatusResponse;
use DevWizard\Payify\Dto\WebhookPayload;
use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Events\PaymentRefunded;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FakeDriver extends AbstractDriver implements HandlesWebhook, SupportsHostedCheckout, SupportsRefund
{
    public function name(): string
    {
        return $this->config['name'] ?? 'fake';
    }

    public function capabilities(): array
    {
        return [
            'refund' => true,
            'tokenization' => false,
            'hosted_checkout' => true,
            'direct_api' => false,
            'webhook' => true,
            'partial_refund' => true,
            'currencies' => ['BDT', 'USD'],
        ];
    }

    public function handleCallback(Request $request): PaymentResponse
    {
        $reference = (string) $request->input('reference', '');
        $txn = Transaction::where('provider', $this->name())
            ->where('reference', $reference)
            ->first();

        return $txn
            ? PaymentResponse::fromTransaction($txn)
            : $this->unknown($reference);
    }

    protected function executePayment(PaymentRequest $req, Transaction $txn): PaymentResponse
    {
        $canned = $this->canned('pay');

        $redirectUrl = $canned['redirect_url'] ?? 'https://fake.payify.test/'.$txn->id;
        $providerTxnId = $canned['provider_transaction_id'] ?? 'fake_'.Str::random(12);
        $status = isset($canned['status'])
            ? TransactionStatus::from($canned['status'])
            : TransactionStatus::Processing;

        return new PaymentResponse(
            transactionId: $txn->id,
            providerTransactionId: $providerTxnId,
            status: $status,
            amount: $req->amount,
            currency: $req->currency,
            redirectUrl: $redirectUrl,
            raw: $canned,
        );
    }

    protected function executeStatus(Transaction $txn): StatusResponse
    {
        $canned = $this->canned('status');
        $status = isset($canned['status'])
            ? TransactionStatus::from($canned['status'])
            : $txn->status;

        return new StatusResponse(
            transactionId: $txn->id,
            status: $status,
            providerTransactionId: $txn->provider_transaction_id,
            paidAmount: (float) $txn->amount,
            refundedAmount: (float) $txn->refunded_amount,
            raw: $canned,
        );
    }

    public function refund(RefundRequest $request): RefundResponse
    {
        $txn = Transaction::findOrFail($request->transactionId);
        $amount = $request->amount ?? (float) $txn->amount;

        $txn->markRefunded($amount);

        $canned = $this->canned('refund');
        $status = isset($canned['status'])
            ? TransactionStatus::from($canned['status'])
            : TransactionStatus::Refunded;

        $response = new RefundResponse(
            transactionId: $txn->id,
            refundId: 'fake_refund_'.Str::random(8),
            amount: $amount,
            status: $status,
        );

        $this->events->dispatch(new PaymentRefunded($txn->fresh(), $response));

        return $response;
    }

    public function verifyWebhook(Request $request): WebhookPayload
    {
        $data = array_merge($request->query->all(), $request->request->all());

        return new WebhookPayload(
            provider: $this->name(),
            event: (string) ($data['event'] ?? 'payment.succeeded'),
            providerTransactionId: $data['provider_transaction_id'] ?? null,
            reference: $data['reference'] ?? null,
            amount: isset($data['amount']) ? (float) $data['amount'] : null,
            currency: $data['currency'] ?? null,
            raw: $data,
            verified: true,
        );
    }

    protected function canned(string $action): array
    {
        return $this->config['canned'][$action] ?? [];
    }

    private function unknown(string $reference): PaymentResponse
    {
        return new PaymentResponse(
            transactionId: '',
            providerTransactionId: null,
            status: TransactionStatus::Failed,
            amount: 0,
            currency: config('payify.default_currency', 'BDT'),
            errorCode: 'NOT_FOUND',
            errorMessage: "No transaction for reference [{$reference}].",
        );
    }
}

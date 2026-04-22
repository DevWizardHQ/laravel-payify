<?php

namespace DevWizard\Payify\Providers\Bkash;

use DevWizard\Payify\Contracts\SupportsAuthCapture;
use DevWizard\Payify\Contracts\SupportsHostedCheckout;
use DevWizard\Payify\Contracts\SupportsPayout;
use DevWizard\Payify\Contracts\SupportsRefund;
use DevWizard\Payify\Contracts\SupportsTokenization;
use DevWizard\Payify\Drivers\AbstractDriver;
use DevWizard\Payify\Dto\Customer;
use DevWizard\Payify\Dto\PaymentRequest;
use DevWizard\Payify\Dto\PaymentResponse;
use DevWizard\Payify\Dto\PayoutRequest;
use DevWizard\Payify\Dto\PayoutResponse;
use DevWizard\Payify\Dto\RefundRequest;
use DevWizard\Payify\Dto\RefundResponse;
use DevWizard\Payify\Dto\StatusResponse;
use DevWizard\Payify\Dto\TokenResponse;
use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Events\AgreementCancelled;
use DevWizard\Payify\Events\AgreementCreated;
use DevWizard\Payify\Events\PaymentCancelled;
use DevWizard\Payify\Events\PaymentCaptured;
use DevWizard\Payify\Events\PaymentFailed;
use DevWizard\Payify\Events\PaymentRefunded;
use DevWizard\Payify\Events\PaymentSucceeded;
use DevWizard\Payify\Events\PaymentVoided;
use DevWizard\Payify\Events\PayoutFailed;
use DevWizard\Payify\Events\PayoutInitiated;
use DevWizard\Payify\Events\PayoutSucceeded;
use DevWizard\Payify\Exceptions\PayifyException;
use DevWizard\Payify\Exceptions\ValidationException;
use DevWizard\Payify\Http\PayifyHttpClient;
use DevWizard\Payify\Models\Agreement;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;

class BkashDriver extends AbstractDriver implements SupportsAuthCapture, SupportsHostedCheckout, SupportsPayout, SupportsRefund, SupportsTokenization
{
    public function __construct(
        PayifyHttpClient $client,
        array $config,
        Dispatcher $events,
        LoggerInterface $logger,
        protected BkashTokenManager $tokens,
        protected BkashRequestBuilder $requestBuilder,
    ) {
        parent::__construct($client, $config, $events, $logger);
    }

    public function name(): string
    {
        return 'bkash';
    }

    public function capabilities(): array
    {
        return [
            'refund' => true,
            'tokenization' => true,
            'hosted_checkout' => true,
            'direct_api' => false,
            'auth_capture' => true,
            'payout' => true,
            'webhook' => false,
            'partial_refund' => true,
            'currencies' => ['BDT'],
        ];
    }

    protected function executePayment(PaymentRequest $req, Transaction $txn): PaymentResponse
    {
        $agreementId = $req->extras['agreement_id'] ?? null;
        $payload = $this->requestBuilder->buildCreate(
            $req,
            $agreementId,
            $this->config['default_mode'] ?? Constants::MODE_CHECKOUT,
            $this->config['default_intent'] ?? Constants::INTENT_SALE,
        );

        $response = $this->postAuthed(Constants::PATH_CREATE, $payload);
        $this->assertStatusOk($response);

        return new PaymentResponse(
            transactionId: $txn->id,
            providerTransactionId: (string) $response['paymentID'],
            status: TransactionStatus::Processing,
            amount: $req->amount,
            currency: 'BDT',
            redirectUrl: (string) $response['bkashURL'],
            sessionId: (string) $response['paymentID'],
            raw: $response,
        );
    }

    public function handleCallback(Request $request): PaymentResponse
    {
        $paymentId = (string) $request->input('paymentID', '');
        $status = (string) $request->input('status', 'success');

        $txn = Transaction::where('provider', $this->name())
            ->where('provider_transaction_id', $paymentId)
            ->first();

        if (! $txn) {
            return new PaymentResponse(
                transactionId: '',
                providerTransactionId: $paymentId,
                status: TransactionStatus::Failed,
                amount: 0, currency: 'BDT',
                errorCode: 'NOT_FOUND',
                errorMessage: "No transaction for paymentID [{$paymentId}].",
            );
        }

        if ($status === 'cancel' || $status === 'cancelled') {
            $txn->markCancelled(['query' => $request->query()]);
            $this->events->dispatch(new PaymentCancelled($txn->fresh()));

            return PaymentResponse::fromTransaction($txn->fresh());
        }

        if ($status === 'failure' || $status === 'failed') {
            $txn->markFailed('USER_CANCELLED_OR_FAILED', 'User did not complete payment', ['query' => $request->query()]);
            $this->events->dispatch(new PaymentFailed($txn->fresh(), 'USER_CANCELLED_OR_FAILED', 'User did not complete payment'));

            return PaymentResponse::fromTransaction($txn->fresh());
        }

        $execResponse = $this->postAuthed(Constants::PATH_EXECUTE, ['paymentID' => $paymentId]);

        $txnStatus = (string) ($execResponse['transactionStatus'] ?? '');
        $code = (string) ($execResponse['statusCode'] ?? '');

        if ($code !== Constants::STATUS_SUCCESS) {
            $this->applyBkashError($txn, $execResponse);

            return PaymentResponse::fromTransaction($txn->fresh());
        }

        if ($txn->intent === Constants::INTENT_AUTH && $txnStatus === Constants::TXN_STATUS_AUTHORIZED) {
            $txn->markAuthorized($execResponse['trxID'] ?? null, $execResponse);

            return PaymentResponse::fromTransaction($txn->fresh());
        }

        if ($txnStatus === Constants::TXN_STATUS_COMPLETED) {
            $txn->markSucceeded($execResponse['trxID'] ?? null, $execResponse);
            $this->events->dispatch(new PaymentSucceeded($txn->fresh()));

            if (! empty($execResponse['agreementID']) && $txn->intent !== Constants::INTENT_AUTH) {
                $this->upsertAgreementFromExecute($execResponse);
            }
        } else {
            $message = (string) ($execResponse['statusMessage'] ?? 'Unknown status');
            $txn->markFailed('BKASH_EXECUTE_'.$txnStatus, $message, $execResponse);
            $this->events->dispatch(new PaymentFailed($txn->fresh(), 'BKASH_EXECUTE_'.$txnStatus, $message));
        }

        return PaymentResponse::fromTransaction($txn->fresh());
    }

    protected function executeStatus(Transaction $txn): StatusResponse
    {
        $response = $this->postAuthed(Constants::PATH_STATUS, [
            'paymentID' => $txn->provider_transaction_id,
        ]);

        return new StatusResponse(
            transactionId: $txn->id,
            status: $this->mapTxnStatus((string) ($response['transactionStatus'] ?? '')),
            providerTransactionId: (string) ($response['trxID'] ?? $txn->provider_transaction_id),
            paidAmount: isset($response['amount']) ? (float) $response['amount'] : null,
            refundedAmount: null,
            raw: $response,
        );
    }

    public function refund(RefundRequest $req): RefundResponse
    {
        $txn = Transaction::findOrFail($req->transactionId);

        if (! $txn->canRefund()) {
            throw new ValidationException("Transaction [{$txn->id}] is not in a refundable state.");
        }

        $trxId = data_get($txn->response_payload, 'trxID')
            ?? throw new ValidationException("Missing trxID on transaction [{$txn->id}].");

        $payload = [
            'paymentID' => $txn->provider_transaction_id,
            'trxID' => $trxId,
        ];
        if ($req->amount !== null) {
            $payload['amount'] = $this->requestBuilder->formatAmount($req->amount);
            $payload['sku'] = $req->extras['sku'] ?? 'refund';
            $payload['reason'] = $req->reason ?? 'refund';
        }

        $response = $this->postAuthed(Constants::PATH_REFUND, $payload);
        $this->assertStatusOk($response);

        $amount = (float) ($response['amount'] ?? $req->amount ?? $txn->amount);
        $txn->markRefunded($amount, $response);

        $refundResponse = new RefundResponse(
            transactionId: $txn->id,
            refundId: (string) ($response['refundTrxID'] ?? ''),
            amount: $amount,
            status: $txn->fresh()->status,
            raw: $response,
        );

        $this->events->dispatch(new PaymentRefunded($txn->fresh(), $refundResponse));

        return $refundResponse;
    }

    public function authorize(PaymentRequest $req): PaymentResponse
    {
        $requested = new PaymentRequest(
            amount: $req->amount, currency: $req->currency, reference: $req->reference,
            customer: $req->customer, callbackUrl: $req->callbackUrl, webhookUrl: $req->webhookUrl,
            mode: $req->mode, intent: Constants::INTENT_AUTH,
            productCategory: $req->productCategory, productName: $req->productName,
            productProfile: $req->productProfile, gateway: $req->gateway,
            emiOption: $req->emiOption, emiMaxInstallments: $req->emiMaxInstallments,
            lineItems: $req->lineItems, payable: $req->payable,
            metadata: $req->metadata, extras: $req->extras,
        );

        return $this->pay($requested);
    }

    public function capture(Transaction $transaction, ?float $amount = null): PaymentResponse
    {
        $response = $this->postAuthed(Constants::PATH_CAPTURE, [
            'paymentID' => $transaction->provider_transaction_id,
        ]);
        $this->assertStatusOk($response);

        $captured = $amount ?? (float) $transaction->amount;
        $transaction->markCaptured($captured, $response);
        $this->events->dispatch(new PaymentCaptured($transaction->fresh(), $captured));

        return PaymentResponse::fromTransaction($transaction->fresh());
    }

    public function void(Transaction $transaction): PaymentResponse
    {
        $response = $this->postAuthed(Constants::PATH_VOID, [
            'paymentID' => $transaction->provider_transaction_id,
        ]);
        $this->assertStatusOk($response);

        $transaction->markVoided($response);
        $this->events->dispatch(new PaymentVoided($transaction->fresh()));

        return PaymentResponse::fromTransaction($transaction->fresh());
    }

    public function tokenize(Customer $customer): TokenResponse
    {
        $payload = [
            'mode' => Constants::MODE_AGREEMENT_CREATE,
            'payerReference' => $this->requestBuilder->sanitize((string) $customer->phone),
            'callbackURL' => (string) ($this->config['agreement_callback_url'] ?? config('payify.routes.prefix').'/callback/bkash/agreement'),
            'amount' => '1.00',
            'currency' => 'BDT',
            'intent' => 'sale',
        ];

        $response = $this->postAuthed(Constants::PATH_CREATE, $payload);
        $this->assertStatusOk($response);

        return new TokenResponse(
            token: (string) $response['paymentID'],
            raw: $response,
        );
    }

    public function chargeToken(string $token, PaymentRequest $req): PaymentResponse
    {
        $requested = new PaymentRequest(
            amount: $req->amount, currency: $req->currency, reference: $req->reference,
            customer: $req->customer ?? new Customer(phone: Agreement::where('agreement_id', $token)->first()?->payer_reference),
            callbackUrl: $req->callbackUrl ?? (string) ($this->config['agreement_callback_url'] ?? config('payify.routes.prefix').'/callback/bkash/charge'),
            webhookUrl: $req->webhookUrl,
            mode: Constants::MODE_AGREEMENT_PAY,
            intent: Constants::INTENT_SALE,
            extras: array_merge($req->extras, ['agreement_id' => $token]),
            metadata: $req->metadata,
            payable: $req->payable,
        );

        return $this->pay($requested);
    }

    public function detokenize(string $token): bool
    {
        $response = $this->postAuthed(Constants::PATH_AGREEMENT_CANCEL, ['agreementID' => $token]);
        $this->assertStatusOk($response);

        $agreement = Agreement::where('provider', $this->name())->where('agreement_id', $token)->first();
        if ($agreement) {
            $agreement->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);
            $this->events->dispatch(new AgreementCancelled($agreement->fresh()));
        }

        return true;
    }

    public function initPayout(PayoutRequest $req): PayoutResponse
    {
        $response = $this->postAuthed(Constants::PATH_PAYOUT_INIT, [
            'reference' => $req->reference,
            'type' => 'B2B',
        ]);
        $this->assertStatusOk($response);

        $txn = $this->recordPayoutTransaction($req, (string) ($response['payoutID'] ?? ''));
        $this->events->dispatch(new PayoutInitiated($txn));

        return new PayoutResponse(
            transactionId: $txn->id,
            providerPayoutId: (string) ($response['payoutID'] ?? ''),
            status: TransactionStatus::Pending,
            amount: $req->amount,
            currency: $req->currency,
            raw: $response,
        );
    }

    public function executePayout(string $payoutId, PayoutRequest $req): PayoutResponse
    {
        $response = $this->postAuthed(Constants::PATH_PAYOUT_EXECUTE, [
            'payoutID' => $payoutId,
            'amount' => $this->requestBuilder->formatAmount($req->amount),
            'currency' => $req->currency,
            'merchantInvoiceNumber' => $this->requestBuilder->sanitize($req->reference),
            'receiverMSISDN' => $req->receiverIdentifier,
        ]);

        $txn = Transaction::where('provider', $this->name())
            ->where('type', 'payout')
            ->where('provider_transaction_id', $payoutId)
            ->first();

        if (($response['statusCode'] ?? '') !== Constants::STATUS_SUCCESS) {
            $code = (string) ($response['statusCode'] ?? 'UNKNOWN');
            $message = (string) ($response['statusMessage'] ?? 'Payout failed');
            if ($txn) {
                $txn->markFailed('BKASH_PAYOUT_'.$code, $message, $response);
                $this->events->dispatch(new PayoutFailed($txn->fresh(), $code, $message));
            }

            return new PayoutResponse(
                transactionId: $txn?->id ?? '',
                providerPayoutId: $payoutId,
                status: TransactionStatus::Failed,
                amount: $req->amount,
                currency: $req->currency,
                errorCode: $code,
                errorMessage: $message,
                raw: $response,
            );
        }

        $txn?->markSucceeded((string) ($response['trxID'] ?? $payoutId), $response);
        if ($txn) {
            $this->events->dispatch(new PayoutSucceeded($txn->fresh()));
        }

        return new PayoutResponse(
            transactionId: $txn?->id ?? '',
            providerPayoutId: $payoutId,
            status: TransactionStatus::Succeeded,
            amount: $req->amount,
            currency: $req->currency,
            raw: $response,
        );
    }

    public function payout(PayoutRequest $req): PayoutResponse
    {
        $init = $this->initPayout($req);

        return $this->executePayout((string) $init->providerPayoutId, $req);
    }

    protected function postAuthed(string $path, array $body, int $retryCount = 0): array
    {
        try {
            $response = $this->client->post(
                $this->baseUrl().$path,
                $body,
                [
                    'Authorization' => $this->tokens->idToken(),
                    'X-APP-Key' => $this->credential('app_key'),
                    'Content-Type' => 'application/json',
                ],
            );

            if (($response['statusCode'] ?? null) === Constants::ERR_INVALID_TOKEN && $retryCount === 0) {
                $this->tokens->forget();

                return $this->postAuthed($path, $body, $retryCount + 1);
            }

            return $response;
        } catch (PayifyException $e) {
            throw $e;
        }
    }

    protected function assertStatusOk(array $response): void
    {
        if (($response['statusCode'] ?? null) === Constants::STATUS_SUCCESS) {
            return;
        }

        throw BkashErrorMap::map(
            (string) ($response['statusCode'] ?? 'UNKNOWN'),
            (string) ($response['statusMessage'] ?? 'Provider error'),
            $response,
        );
    }

    protected function applyBkashError(Transaction $txn, array $response): void
    {
        $code = (string) ($response['statusCode'] ?? 'UNKNOWN');
        $message = (string) ($response['statusMessage'] ?? 'Provider error');
        $txn->markFailed('BKASH_'.$code, $message, $response);
        $this->events->dispatch(new PaymentFailed($txn->fresh(), 'BKASH_'.$code, $message));
    }

    protected function upsertAgreementFromExecute(array $response): void
    {
        $agreement = Agreement::updateOrCreate(
            [
                'provider' => $this->name(),
                'agreement_id' => (string) $response['agreementID'],
            ],
            [
                'payer_reference' => (string) ($response['payerReference'] ?? ''),
                'status' => strtolower((string) ($response['agreementStatus'] ?? 'active')) === 'active' ? 'active' : 'cancelled',
                'activated_at' => now(),
                'metadata' => ['from_execute' => true],
            ],
        );

        $this->events->dispatch(new AgreementCreated($agreement));
    }

    protected function recordPayoutTransaction(PayoutRequest $req, string $payoutId): Transaction
    {
        return Transaction::create([
            'provider' => $this->name(),
            'type' => 'payout',
            'reference' => $req->reference,
            'amount' => $req->amount,
            'currency' => $req->currency,
            'status' => TransactionStatus::Pending,
            'provider_transaction_id' => $payoutId,
            'customer' => ['phone' => $req->receiverIdentifier, 'name' => $req->receiverName],
            'request_payload' => ['reference' => $req->reference, 'receiver' => $req->receiverIdentifier],
        ]);
    }

    protected function mapTxnStatus(string $bkashStatus): TransactionStatus
    {
        return match ($bkashStatus) {
            Constants::TXN_STATUS_COMPLETED => TransactionStatus::Succeeded,
            Constants::TXN_STATUS_AUTHORIZED => TransactionStatus::Processing,
            Constants::TXN_STATUS_INITIATED => TransactionStatus::Processing,
            Constants::TXN_STATUS_FAILED => TransactionStatus::Failed,
            Constants::TXN_STATUS_CANCELLED => TransactionStatus::Cancelled,
            default => TransactionStatus::Processing,
        };
    }
}

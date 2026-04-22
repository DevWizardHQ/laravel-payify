<?php

namespace DevWizard\Payify\Providers\Sslcommerz;

use DevWizard\Payify\Contracts\HandlesWebhook;
use DevWizard\Payify\Contracts\SupportsEmbeddedCheckout;
use DevWizard\Payify\Contracts\SupportsEmi;
use DevWizard\Payify\Contracts\SupportsHostedCheckout;
use DevWizard\Payify\Contracts\SupportsRefund;
use DevWizard\Payify\Contracts\SupportsRefundQuery;
use DevWizard\Payify\Drivers\AbstractDriver;
use DevWizard\Payify\Dto\PaymentRequest;
use DevWizard\Payify\Dto\PaymentResponse;
use DevWizard\Payify\Dto\RefundRequest;
use DevWizard\Payify\Dto\RefundResponse;
use DevWizard\Payify\Dto\StatusResponse;
use DevWizard\Payify\Dto\WebhookPayload;
use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Events\PaymentCancelled;
use DevWizard\Payify\Events\PaymentFailed;
use DevWizard\Payify\Events\PaymentRefunded;
use DevWizard\Payify\Events\PaymentSucceeded;
use DevWizard\Payify\Exceptions\ValidationException;
use DevWizard\Payify\Http\PayifyHttpClient;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

class SslcommerzDriver extends AbstractDriver implements HandlesWebhook, SupportsEmbeddedCheckout, SupportsEmi, SupportsHostedCheckout, SupportsRefund, SupportsRefundQuery
{
    protected SslcommerzGateway $gateway;

    protected SslcommerzValidator $validator;

    protected SslcommerzRefundClient $refundClient;

    protected SslcommerzIpnVerifier $ipnVerifier;

    protected SslcommerzPayloadBuilder $payloadBuilder;

    public function __construct(
        PayifyHttpClient $client,
        array $config,
        Dispatcher $events,
        LoggerInterface $logger,
        ?SslcommerzGateway $gateway = null,
        ?SslcommerzValidator $validator = null,
        ?SslcommerzRefundClient $refundClient = null,
        ?SslcommerzIpnVerifier $ipnVerifier = null,
        ?SslcommerzPayloadBuilder $payloadBuilder = null,
    ) {
        parent::__construct($client, $config, $events, $logger);

        $this->payloadBuilder = $payloadBuilder ?? new SslcommerzPayloadBuilder($config);
        $this->validator = $validator ?? new SslcommerzValidator($client, $config);
        $this->gateway = $gateway ?? new SslcommerzGateway($client, $config, $this->payloadBuilder);
        $this->refundClient = $refundClient ?? new SslcommerzRefundClient($client, $config);
        $this->ipnVerifier = $ipnVerifier ?? new SslcommerzIpnVerifier($config, $this->validator);
    }

    public function name(): string
    {
        return 'sslcommerz';
    }

    public function capabilities(): array
    {
        return [
            'refund' => true,
            'refund_query' => true,
            'tokenization' => false,
            'hosted_checkout' => true,
            'direct_api' => false,
            'auth_capture' => false,
            'payout' => false,
            'webhook' => true,
            'partial_refund' => true,
            'emi' => true,
            'embedded_checkout' => true,
            'currencies' => ['BDT', 'USD', 'EUR', 'GBP', 'INR', 'SGD', 'MYR'],
        ];
    }

    protected function executePayment(PaymentRequest $req, Transaction $txn): PaymentResponse
    {
        $response = $this->gateway->initSession($req);

        return new PaymentResponse(
            transactionId: $txn->id,
            providerTransactionId: (string) ($response['sessionkey'] ?? ''),
            status: TransactionStatus::Processing,
            amount: $req->amount,
            currency: $req->currency,
            redirectUrl: (string) ($response['redirectUrl'] ?? $response['GatewayPageURL'] ?? ''),
            sessionId: (string) ($response['sessionkey'] ?? ''),
            raw: $response,
        );
    }

    protected function executeStatus(Transaction $txn): StatusResponse
    {
        $response = $this->validator->queryByTranId($txn->reference);
        $status = $this->mapStatus((string) ($response['status'] ?? ''));

        return new StatusResponse(
            transactionId: $txn->id,
            status: $status,
            providerTransactionId: (string) ($response['bank_tran_id'] ?? $txn->provider_transaction_id),
            paidAmount: isset($response['amount']) ? (float) $response['amount'] : null,
            refundedAmount: null,
            raw: $response,
        );
    }

    public function handleCallback(Request $request): PaymentResponse
    {
        $tranId = (string) $request->input('tran_id', '');

        if ($tranId === '') {
            return new PaymentResponse(
                transactionId: '',
                providerTransactionId: null,
                status: TransactionStatus::Failed,
                amount: 0,
                currency: config('payify.default_currency', 'BDT'),
                errorCode: 'MISSING_TRAN_ID',
                errorMessage: 'Callback did not include tran_id.',
            );
        }

        $txn = Transaction::where('provider', $this->name())->where('reference', $tranId)->first();

        if (! $txn) {
            return new PaymentResponse(
                transactionId: '',
                providerTransactionId: null,
                status: TransactionStatus::Failed,
                amount: 0,
                currency: config('payify.default_currency', 'BDT'),
                errorCode: 'NOT_FOUND',
                errorMessage: "No transaction for tran_id [{$tranId}].",
            );
        }

        if ($txn->webhook_verified_at !== null) {
            return PaymentResponse::fromTransaction($txn);
        }

        $valId = (string) $request->input('val_id', '');
        if ($valId !== '') {
            $validation = $this->validator->validateByValId($valId);
            $this->applyValidation($txn, $validation);
        }

        return PaymentResponse::fromTransaction($txn->fresh());
    }

    public function refund(RefundRequest $req): RefundResponse
    {
        $txn = Transaction::findOrFail($req->transactionId);

        if (! $txn->canRefund()) {
            throw new ValidationException("Transaction [{$txn->id}] is not in a refundable state.");
        }

        $bankTranId = $txn->provider_transaction_id
            ?: throw new ValidationException("Missing bank_tran_id on transaction [{$txn->id}].");

        $refundTransId = $req->extras['refund_trans_id']
            ?? $txn->reference.'-REF-'.now()->timestamp.'-'.Str::upper(Str::random(6));
        $amount = $req->amount ?? (float) $txn->remainingRefundable();

        $response = $this->refundClient->initiate(
            bankTranId: $bankTranId,
            refundTransId: $refundTransId,
            amount: $amount,
            remarks: $req->reason ?? 'refund',
        );

        $apiStatus = (string) ($response['APIConnect'] ?? '');
        if ($apiStatus !== Constants::API_CONNECT_DONE) {
            throw SslcommerzErrorMap::map($apiStatus, (string) ($response['errorReason'] ?? 'Refund failed'), $response);
        }

        $refundStatus = (string) ($response['status'] ?? 'processing');
        $refundRefId = (string) ($response['refund_ref_id'] ?? '');

        // Build merged payload once; markRefunded will persist it if we call it below.
        $payload = $txn->response_payload ?? [];
        $payload['refund'] = [
            'refund_ref_id' => $refundRefId,
            'refund_trans_id' => $refundTransId,
            'status' => $refundStatus,
        ];

        if ($refundStatus === Constants::REFUND_STATUS_REFUNDED) {
            // Bank confirmed the refund; flip transaction state.
            $txn->markRefunded($amount, $payload);
        } else {
            // Processing / unknown: persist refund_ref_id but keep transaction in its
            // current state. Merchants can poll via `payify:refund:status` to confirm.
            $txn->response_payload = $payload;
            $txn->save();
        }

        $status = $txn->fresh()->status;

        $refundResponse = new RefundResponse(
            transactionId: $txn->id,
            refundId: $refundRefId,
            amount: $amount,
            status: $status,
            raw: $response,
        );

        if ($refundStatus === Constants::REFUND_STATUS_REFUNDED) {
            $this->events->dispatch(new PaymentRefunded($txn->fresh(), $refundResponse));
        }

        return $refundResponse;
    }

    public function queryRefund(string $refundRefId): array
    {
        return $this->refundClient->query($refundRefId);
    }

    public function verifyWebhook(Request $request): WebhookPayload
    {
        return $this->ipnVerifier->verify($request);
    }

    public function emiOptions(): array
    {
        return [
            'enabled' => true,
            'max_installments' => 12,
        ];
    }

    public function buildEmiPayload(PaymentRequest $request): array
    {
        $payload = [];
        if ($request->emiOption !== null) {
            $payload['emi_option'] = (string) $request->emiOption;
        }
        if ($request->emiMaxInstallments !== null) {
            $payload['emi_max_inst_option'] = $request->emiMaxInstallments;
        }

        return $payload;
    }

    public function embedScript(): string
    {
        $mode = $this->mode();

        return (string) $this->config['embed'][$mode === 'live' ? 'live_script' : 'sandbox_script'];
    }

    public function embedAttributes(PaymentRequest $request): array
    {
        $postdata = $this->payloadBuilder->build($request);

        // Strip merchant secrets before exposing to browser.
        // SSLCommerz's embed widget accepts only non-credential fields here;
        // the actual init call runs server-side when rendering the page.
        foreach (['store_passwd', 'store_id'] as $secret) {
            unset($postdata[$secret]);
        }

        return [
            'data-sslcommerz' => 'checkout',
            'data-postdata' => (string) json_encode($postdata),
        ];
    }

    protected function applyValidation(Transaction $txn, array $validation): void
    {
        $status = (string) ($validation['status'] ?? '');

        if (in_array($status, [Constants::STATUS_VALID, Constants::STATUS_VALIDATED], true)) {
            $txn->markSucceeded((string) ($validation['bank_tran_id'] ?? ''), $validation);
            $this->events->dispatch(new PaymentSucceeded($txn->fresh()));

            return;
        }

        if ($status === Constants::STATUS_FAILED) {
            $txn->markFailed('SSLCZ_FAILED', (string) ($validation['failedreason'] ?? 'Payment failed'), $validation);
            $this->events->dispatch(new PaymentFailed($txn->fresh(), 'SSLCZ_FAILED', (string) ($validation['failedreason'] ?? 'Payment failed')));

            return;
        }

        if ($status === Constants::STATUS_CANCELLED) {
            $txn->markCancelled($validation);
            $this->events->dispatch(new PaymentCancelled($txn->fresh()));
        }
    }

    protected function mapStatus(string $status): TransactionStatus
    {
        return match ($status) {
            Constants::STATUS_VALID, Constants::STATUS_VALIDATED => TransactionStatus::Succeeded,
            Constants::STATUS_PENDING => TransactionStatus::Processing,
            Constants::STATUS_FAILED, Constants::STATUS_INVALID_TRANSACTION => TransactionStatus::Failed,
            Constants::STATUS_CANCELLED => TransactionStatus::Cancelled,
            default => TransactionStatus::Processing,
        };
    }
}

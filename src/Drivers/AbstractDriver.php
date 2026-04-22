<?php

namespace DevWizard\Payify\Drivers;

use DevWizard\Payify\Contracts\PaymentProvider;
use DevWizard\Payify\Dto\PaymentRequest;
use DevWizard\Payify\Dto\PaymentResponse;
use DevWizard\Payify\Dto\StatusResponse;
use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Events\PaymentFailed;
use DevWizard\Payify\Events\PaymentInitiated;
use DevWizard\Payify\Events\PaymentStatusChecked;
use DevWizard\Payify\Exceptions\InvalidCredentialsException;
use DevWizard\Payify\Exceptions\PayifyException;
use DevWizard\Payify\Exceptions\PaymentFailedException;
use DevWizard\Payify\Exceptions\ProviderNotFoundException;
use DevWizard\Payify\Exceptions\ValidationException;
use DevWizard\Payify\Http\PayifyHttpClient;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Throwable;

abstract class AbstractDriver implements PaymentProvider
{
    public function __construct(
        protected PayifyHttpClient $client,
        protected array $config,
        protected Dispatcher $events,
        protected LoggerInterface $logger,
    ) {}

    abstract public function name(): string;

    abstract public function capabilities(): array;

    abstract public function handleCallback(Request $request): PaymentResponse;

    abstract protected function executePayment(PaymentRequest $req, Transaction $txn): PaymentResponse;

    abstract protected function executeStatus(Transaction $txn): StatusResponse;

    final public function pay(PaymentRequest $request): PaymentResponse
    {
        $this->validate($request);

        if ($existing = $this->findIdempotent($request)) {
            return PaymentResponse::fromTransaction($existing);
        }

        $txn = $this->recordTransaction($request);
        $this->events->dispatch(new PaymentInitiated($txn));

        try {
            $response = $this->executePayment($request, $txn);
            $this->applyResponse($txn, $response);

            return $response;
        } catch (Throwable $e) {
            return $this->handleFailure($txn, $e);
        }
    }

    final public function status(Transaction $transaction): StatusResponse
    {
        $response = $this->executeStatus($transaction);
        $transaction->refreshFromStatus($response);
        $this->events->dispatch(new PaymentStatusChecked($transaction, $response));

        return $response;
    }

    protected function mode(): string
    {
        return $this->config['mode'] ?? config('payify.mode', 'sandbox');
    }

    protected function credential(string $key, mixed $default = null): mixed
    {
        return $this->config['credentials'][$key] ?? $default;
    }

    protected function baseUrl(): string
    {
        $key = $this->mode() === 'live' ? 'live_url' : 'sandbox_url';

        return (string) ($this->config[$key] ?? '');
    }

    protected function validate(PaymentRequest $req): void
    {
        if ($req->amount <= 0) {
            throw new ValidationException('Amount must be greater than zero.');
        }
        if ($req->reference === '') {
            throw new ValidationException('Reference is required.');
        }

        $currencies = $this->capabilities()['currencies'] ?? [];
        if ($currencies && ! in_array($req->currency, $currencies, true)) {
            throw new ValidationException("Currency [{$req->currency}] not supported by provider [{$this->name()}].");
        }
    }

    protected function findIdempotent(PaymentRequest $req): ?Transaction
    {
        if (! config('payify.idempotency.enabled', true)) {
            return null;
        }

        return Transaction::where('provider', $this->name())
            ->where('reference', $req->reference)
            ->whereIn('status', [
                TransactionStatus::Pending->value,
                TransactionStatus::Processing->value,
                TransactionStatus::Succeeded->value,
            ])
            ->first();
    }

    protected function recordTransaction(PaymentRequest $req): Transaction
    {
        return Transaction::create([
            'provider' => $this->name(),
            'reference' => $req->reference,
            'amount' => $req->amount,
            'currency' => $req->currency,
            'status' => TransactionStatus::Pending,
            'intent' => $req->intent,
            'customer' => $req->customer?->toArray(),
            'metadata' => $req->metadata,
            'request_payload' => $req->toArray(),
            'payable_type' => $req->payable ? $req->payable::class : null,
            'payable_id' => $req->payable?->getKey(),
        ]);
    }

    protected function applyResponse(Transaction $txn, PaymentResponse $res): void
    {
        $txn->update([
            'status' => $res->status,
            'provider_transaction_id' => $res->providerTransactionId ?? $txn->provider_transaction_id,
            'response_payload' => $res->raw ?: $txn->response_payload,
        ]);
    }

    protected function handleFailure(Transaction $txn, Throwable $e): PaymentResponse
    {
        $this->logger->error('payify.payment.failed', [
            'provider' => $this->name(),
            'transaction_id' => $txn->id,
            'exception' => $e::class,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        [$code, $message] = $this->extractError($e);
        $txn->markFailed($code, $message);
        $this->events->dispatch(new PaymentFailed($txn, $code, $message));

        if ($e instanceof ProviderNotFoundException
            || $e instanceof InvalidCredentialsException
            || $e instanceof ValidationException) {
            throw $e;
        }

        if ($this->throwsExceptions()) {
            if ($e instanceof PayifyException) {
                throw $e;
            }
            throw (new PaymentFailedException($message, 0, $e))
                ->setProviderError($code, $message);
        }

        return new PaymentResponse(
            transactionId: $txn->id,
            providerTransactionId: null,
            status: TransactionStatus::Failed,
            amount: (float) $txn->amount,
            currency: $txn->currency,
            errorCode: $code,
            errorMessage: $this->sanitizeForProduction($message),
        );
    }

    protected function extractError(Throwable $e): array
    {
        if ($e instanceof PaymentFailedException) {
            return [
                $e->providerErrorCode() ?? 'PROVIDER_FAILURE',
                $e->providerErrorMessage() ?? $e->getMessage(),
            ];
        }

        return ['PROVIDER_FAILURE', $e->getMessage()];
    }

    protected function throwsExceptions(): bool
    {
        $flag = config('payify.throw_exceptions');

        return $flag ?? (bool) config('app.debug');
    }

    protected function sanitizeForProduction(string $message): string
    {
        return $this->throwsExceptions()
            ? $message
            : 'Payment could not be processed. Please try again.';
    }
}

<?php

use DevWizard\Payify\Builders\AgreementBuilder;
use DevWizard\Payify\Builders\PaymentBuilder;
use DevWizard\Payify\Contracts\PaymentProvider;
use DevWizard\Payify\Contracts\SupportsTokenization;
use DevWizard\Payify\Dto\Customer;
use DevWizard\Payify\Dto\PaymentRequest;
use DevWizard\Payify\Dto\PaymentResponse;
use DevWizard\Payify\Dto\StatusResponse;
use DevWizard\Payify\Dto\TokenResponse;
use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Exceptions\UnsupportedOperationException;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Http\Request;

it('throws when driver does not support tokenization', function () {
    $driver = new class implements PaymentProvider
    {
        public function name(): string
        {
            return 'x';
        }

        public function capabilities(): array
        {
            return [];
        }

        public function pay(PaymentRequest $request): PaymentResponse
        {
            throw new LogicException;
        }

        public function status(Transaction $transaction): StatusResponse
        {
            throw new LogicException;
        }

        public function handleCallback(Request $request): PaymentResponse
        {
            throw new LogicException;
        }
    };

    $builder = new PaymentBuilder($driver);

    expect(fn () => $builder->agreement())->toThrow(UnsupportedOperationException::class);
});

it('returns AgreementBuilder when driver supports tokenization', function () {
    $driver = new class implements PaymentProvider, SupportsTokenization
    {
        public function name(): string
        {
            return 'x';
        }

        public function capabilities(): array
        {
            return [];
        }

        public function pay(PaymentRequest $request): PaymentResponse
        {
            throw new LogicException;
        }

        public function status(Transaction $transaction): StatusResponse
        {
            throw new LogicException;
        }

        public function handleCallback(Request $request): PaymentResponse
        {
            throw new LogicException;
        }

        public function tokenize(Customer $customer): TokenResponse
        {
            throw new LogicException;
        }

        public function chargeToken(string $token, PaymentRequest $request): PaymentResponse
        {
            throw new LogicException;
        }

        public function detokenize(string $token): bool
        {
            return true;
        }
    };

    $builder = new PaymentBuilder($driver);

    expect($builder->agreement())->toBeInstanceOf(AgreementBuilder::class);
});

it('cancels a known agreement via detokenize', function () {
    $calls = [];
    $driver = new class($calls) implements PaymentProvider, SupportsTokenization
    {
        public function __construct(public array &$calls) {}

        public function name(): string
        {
            return 'x';
        }

        public function capabilities(): array
        {
            return [];
        }

        public function pay(PaymentRequest $request): PaymentResponse
        {
            throw new LogicException;
        }

        public function status(Transaction $transaction): StatusResponse
        {
            throw new LogicException;
        }

        public function handleCallback(Request $request): PaymentResponse
        {
            throw new LogicException;
        }

        public function tokenize(Customer $customer): TokenResponse
        {
            throw new LogicException;
        }

        public function chargeToken(string $token, PaymentRequest $request): PaymentResponse
        {
            return new PaymentResponse(
                transactionId: 't',
                providerTransactionId: null,
                status: TransactionStatus::Succeeded,
                amount: $request->amount,
                currency: $request->currency,
            );
        }

        public function detokenize(string $token): bool
        {
            $this->calls[] = $token;

            return true;
        }
    };

    $builder = new AgreementBuilder($driver, agreementId: 'AGR-1');

    expect($builder->cancel())->toBeTrue();
    expect($calls)->toBe(['AGR-1']);
});

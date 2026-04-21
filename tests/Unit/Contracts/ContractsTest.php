<?php

use DevWizard\Payify\Contracts\HandlesWebhook;
use DevWizard\Payify\Contracts\PaymentProvider;
use DevWizard\Payify\Contracts\SupportsDirectApi;
use DevWizard\Payify\Contracts\SupportsHostedCheckout;
use DevWizard\Payify\Contracts\SupportsRefund;
use DevWizard\Payify\Contracts\SupportsTokenization;
use DevWizard\Payify\Dto\PaymentRequest;
use DevWizard\Payify\Dto\PaymentResponse;
use DevWizard\Payify\Dto\StatusResponse;
use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Http\Request;

it('exposes all contracts as interfaces', function () {
    foreach ([
        PaymentProvider::class,
        SupportsRefund::class,
        SupportsTokenization::class,
        SupportsHostedCheckout::class,
        SupportsDirectApi::class,
        HandlesWebhook::class,
    ] as $contract) {
        expect(interface_exists($contract))->toBeTrue("Missing: $contract");
    }
});

it('defines required methods on PaymentProvider', function () {
    $methods = get_class_methods(new class implements PaymentProvider
    {
        public function name(): string
        {
            return '';
        }

        public function capabilities(): array
        {
            return [];
        }

        public function pay(PaymentRequest $request): PaymentResponse
        {
            return new PaymentResponse(
                transactionId: '', providerTransactionId: null,
                status: TransactionStatus::Pending,
                amount: 0, currency: 'BDT',
            );
        }

        public function status(Transaction $transaction): StatusResponse
        {
            return new StatusResponse(
                transactionId: '', status: TransactionStatus::Pending,
            );
        }

        public function handleCallback(Request $request): PaymentResponse
        {
            return new PaymentResponse(
                transactionId: '', providerTransactionId: null,
                status: TransactionStatus::Pending,
                amount: 0, currency: 'BDT',
            );
        }
    });

    expect($methods)->toContain('name', 'capabilities', 'pay', 'status', 'handleCallback');
});

<?php

use DevWizard\Payify\Contracts\PaymentProvider;
use DevWizard\Payify\Contracts\SupportsPayout;
use DevWizard\Payify\Dto\PaymentRequest;
use DevWizard\Payify\Dto\PaymentResponse;
use DevWizard\Payify\Dto\PayoutRequest;
use DevWizard\Payify\Dto\PayoutResponse;
use DevWizard\Payify\Dto\StatusResponse;
use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Managers\PayifyManager;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

beforeEach(function () {
    $provider = new class implements PaymentProvider, SupportsPayout
    {
        public function name(): string
        {
            return 'pofake';
        }

        public function capabilities(): array
        {
            return [];
        }

        public function pay(PaymentRequest $r): PaymentResponse
        {
            throw new LogicException;
        }

        public function status(Transaction $t): StatusResponse
        {
            throw new LogicException;
        }

        public function handleCallback(Request $r): PaymentResponse
        {
            throw new LogicException;
        }

        public function initPayout(PayoutRequest $r): PayoutResponse
        {
            throw new LogicException;
        }

        public function executePayout(string $id, PayoutRequest $r): PayoutResponse
        {
            throw new LogicException;
        }

        public function payout(PayoutRequest $r): PayoutResponse
        {
            return new PayoutResponse(
                transactionId: 'uuid-po', providerPayoutId: 'po_fake',
                status: TransactionStatus::Succeeded, amount: $r->amount, currency: $r->currency,
            );
        }
    };
    app(PayifyManager::class)->extend('pofake', fn () => $provider);
    config()->set('payify.default', 'pofake');
    config()->set('payify.providers.pofake', ['mode' => 'sandbox', 'credentials' => []]);
});

it('runs a payout via CLI', function () {
    $this->artisan('payify:payout', [
        '--amount' => '5000',
        '--receiver' => '01712345678',
        '--reference' => 'PAY-1',
        '--provider' => 'pofake',
    ])->assertSuccessful();
});

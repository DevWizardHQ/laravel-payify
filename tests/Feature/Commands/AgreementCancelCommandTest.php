<?php

use DevWizard\Payify\Contracts\PaymentProvider;
use DevWizard\Payify\Contracts\SupportsTokenization;
use DevWizard\Payify\Dto\Customer;
use DevWizard\Payify\Dto\PaymentRequest;
use DevWizard\Payify\Dto\PaymentResponse;
use DevWizard\Payify\Dto\StatusResponse;
use DevWizard\Payify\Dto\TokenResponse;
use DevWizard\Payify\Managers\PayifyManager;
use DevWizard\Payify\Models\Agreement;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

beforeEach(function () {
    $provider = new class implements PaymentProvider, SupportsTokenization
    {
        public function name(): string
        {
            return 'tokfake';
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

        public function tokenize(Customer $c): TokenResponse
        {
            throw new LogicException;
        }

        public function chargeToken(string $token, PaymentRequest $r): PaymentResponse
        {
            throw new LogicException;
        }

        public function detokenize(string $token): bool
        {
            Agreement::where('agreement_id', $token)->update(['status' => 'cancelled', 'cancelled_at' => now()]);

            return true;
        }
    };
    app(PayifyManager::class)->extend('tokfake', fn () => $provider);
    config()->set('payify.providers.tokfake', ['mode' => 'sandbox', 'credentials' => []]);
});

it('cancels an agreement via provider', function () {
    Agreement::create([
        'provider' => 'tokfake', 'agreement_id' => 'AGR-CAN',
        'payer_reference' => '017', 'status' => 'active',
    ]);

    $this->artisan('payify:agreement:cancel', ['agreement_id' => 'AGR-CAN'])
        ->assertSuccessful();

    expect(Agreement::where('agreement_id', 'AGR-CAN')->first()->status)->toBe('cancelled');
});

it('fails when agreement not found', function () {
    $this->artisan('payify:agreement:cancel', ['agreement_id' => 'MISSING'])
        ->assertFailed();
});

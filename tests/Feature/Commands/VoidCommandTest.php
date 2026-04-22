<?php

use DevWizard\Payify\Contracts\PaymentProvider;
use DevWizard\Payify\Contracts\SupportsAuthCapture;
use DevWizard\Payify\Dto\PaymentRequest;
use DevWizard\Payify\Dto\PaymentResponse;
use DevWizard\Payify\Dto\StatusResponse;
use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Managers\PayifyManager;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

beforeEach(function () {
    $provider = new class implements PaymentProvider, SupportsAuthCapture
    {
        public function name(): string
        {
            return 'voidfake';
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

        public function authorize(PaymentRequest $r): PaymentResponse
        {
            throw new LogicException;
        }

        public function capture(Transaction $t, ?float $amount = null): PaymentResponse
        {
            throw new LogicException;
        }

        public function void(Transaction $t): PaymentResponse
        {
            $t->markVoided();

            return PaymentResponse::fromTransaction($t->fresh());
        }
    };
    app(PayifyManager::class)->extend('voidfake', fn () => $provider);
    config()->set('payify.providers.voidfake', ['mode' => 'sandbox', 'credentials' => []]);
});

it('voids an authorized transaction after confirmation', function () {
    $t = Transaction::create([
        'provider' => 'voidfake', 'reference' => 'VO-1', 'amount' => 100,
        'currency' => 'BDT', 'status' => TransactionStatus::Processing,
    ]);

    $this->artisan('payify:void', ['transaction_id' => $t->id])
        ->expectsConfirmation("Void transaction {$t->id}?", 'yes')
        ->assertSuccessful();

    expect($t->fresh()->status)->toBe(TransactionStatus::Cancelled);
    expect($t->fresh()->voided_at)->not->toBeNull();
});

it('aborts void when user declines confirmation', function () {
    $t = Transaction::create([
        'provider' => 'voidfake', 'reference' => 'VO-NO', 'amount' => 100,
        'currency' => 'BDT', 'status' => TransactionStatus::Processing,
    ]);

    $this->artisan('payify:void', ['transaction_id' => $t->id])
        ->expectsConfirmation("Void transaction {$t->id}?", 'no')
        ->assertSuccessful();

    expect($t->fresh()->status)->toBe(TransactionStatus::Processing);
});

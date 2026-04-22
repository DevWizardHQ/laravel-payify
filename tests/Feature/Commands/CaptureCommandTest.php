<?php

use DevWizard\Payify\Contracts\PaymentProvider;
use DevWizard\Payify\Contracts\SupportsAuthCapture;
use DevWizard\Payify\Drivers\FakeDriver;
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
            return 'capfake';
        }

        public function capabilities(): array
        {
            return [];
        }

        public function pay(PaymentRequest $r): PaymentResponse
        {
            throw new LogicException('unused');
        }

        public function status(Transaction $t): StatusResponse
        {
            throw new LogicException('unused');
        }

        public function handleCallback(Request $r): PaymentResponse
        {
            throw new LogicException('unused');
        }

        public function authorize(PaymentRequest $r): PaymentResponse
        {
            throw new LogicException('unused');
        }

        public function capture(Transaction $t, ?float $amount = null): PaymentResponse
        {
            $t->markCaptured($amount);

            return PaymentResponse::fromTransaction($t->fresh());
        }

        public function void(Transaction $t): PaymentResponse
        {
            throw new LogicException('unused');
        }
    };
    app(PayifyManager::class)->extend('capfake', fn () => $provider);
    config()->set('payify.providers.capfake', ['mode' => 'sandbox', 'credentials' => []]);
});

it('captures an authorized transaction', function () {
    $t = Transaction::create([
        'provider' => 'capfake', 'reference' => 'AU-1', 'amount' => 100,
        'currency' => 'BDT', 'status' => TransactionStatus::Processing,
        'intent' => 'authorization',
    ]);

    $this->artisan('payify:capture', ['transaction_id' => $t->id])
        ->assertSuccessful();

    expect($t->fresh()->status)->toBe(TransactionStatus::Succeeded);
    expect($t->fresh()->captured_at)->not->toBeNull();
});

it('fails when provider does not support capture', function () {
    config()->set('payify.providers.nocap', [
        'driver' => FakeDriver::class,
        'mode' => 'sandbox', 'credentials' => [],
    ]);
    $t = Transaction::create([
        'provider' => 'nocap', 'reference' => 'NOC', 'amount' => 100,
        'currency' => 'BDT', 'status' => TransactionStatus::Processing,
    ]);

    $this->artisan('payify:capture', ['transaction_id' => $t->id])
        ->assertFailed();
});

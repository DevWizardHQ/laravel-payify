<?php

use DevWizard\Payify\Builders\PayoutBuilder;
use DevWizard\Payify\Contracts\SupportsPayout;
use DevWizard\Payify\Drivers\FakeDriver;
use DevWizard\Payify\Dto\PayoutRequest;
use DevWizard\Payify\Dto\PayoutResponse;
use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Exceptions\UnsupportedOperationException;
use DevWizard\Payify\Facades\Payify;

it('throws when driver does not support payout', function () {
    config()->set('payify.default', 'fake');
    config()->set('payify.providers.fake', [
        'driver' => FakeDriver::class,
        'mode' => 'sandbox',
        'credentials' => [],
    ]);

    expect(fn () => Payify::driver('fake')->payout())
        ->toThrow(UnsupportedOperationException::class);
});

it('dispatches send() to driver->payout()', function () {
    $driver = new class implements SupportsPayout
    {
        public array $calls = [];

        public function initPayout(PayoutRequest $r): PayoutResponse
        {
            $this->calls[] = ['init', $r];

            return new PayoutResponse('id', 'po', TransactionStatus::Pending, $r->amount, $r->currency);
        }

        public function executePayout(string $id, PayoutRequest $r): PayoutResponse
        {
            $this->calls[] = ['exec', $id, $r];

            return new PayoutResponse('id', $id, TransactionStatus::Succeeded, $r->amount, $r->currency);
        }

        public function payout(PayoutRequest $r): PayoutResponse
        {
            $this->calls[] = ['payout', $r];

            return new PayoutResponse('id', 'po', TransactionStatus::Succeeded, $r->amount, $r->currency);
        }
    };

    $builder = new PayoutBuilder($driver);
    $response = $builder
        ->amount(5000, 'BDT')
        ->reference('PO-1')
        ->receiver('01712345678', name: 'Vendor')
        ->send();

    expect($response)->toBeInstanceOf(PayoutResponse::class);
    expect($driver->calls[0][0])->toBe('payout');
    expect($driver->calls[0][1]->amount)->toBe(5000.0);
    expect($driver->calls[0][1]->receiverIdentifier)->toBe('01712345678');
});

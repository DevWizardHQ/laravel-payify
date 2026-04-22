<?php

namespace DevWizard\Payify\Contracts;

use DevWizard\Payify\Dto\PaymentRequest;
use DevWizard\Payify\Dto\PaymentResponse;
use DevWizard\Payify\Models\Transaction;

interface SupportsAuthCapture
{
    public function authorize(PaymentRequest $request): PaymentResponse;

    public function capture(Transaction $transaction, ?float $amount = null): PaymentResponse;

    public function void(Transaction $transaction): PaymentResponse;
}

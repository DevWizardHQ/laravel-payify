<?php

namespace DevWizard\Payify\Contracts;

use DevWizard\Payify\Dto\PaymentRequest;
use DevWizard\Payify\Dto\PaymentResponse;
use DevWizard\Payify\Dto\StatusResponse;
use DevWizard\Payify\Models\Transaction;
use Illuminate\Http\Request;

interface PaymentProvider
{
    public function name(): string;

    /**
     * @return array{
     *   refund: bool,
     *   tokenization: bool,
     *   hosted_checkout: bool,
     *   direct_api: bool,
     *   webhook: bool,
     *   partial_refund: bool,
     *   currencies: string[]
     * }
     */
    public function capabilities(): array;

    public function pay(PaymentRequest $request): PaymentResponse;

    public function status(Transaction $transaction): StatusResponse;

    public function handleCallback(Request $request): PaymentResponse;
}

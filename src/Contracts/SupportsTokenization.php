<?php

namespace DevWizard\Payify\Contracts;

use DevWizard\Payify\Dto\Customer;
use DevWizard\Payify\Dto\PaymentRequest;
use DevWizard\Payify\Dto\PaymentResponse;
use DevWizard\Payify\Dto\TokenResponse;

interface SupportsTokenization
{
    public function tokenize(Customer $customer): TokenResponse;

    public function chargeToken(string $token, PaymentRequest $request): PaymentResponse;

    public function detokenize(string $token): bool;
}

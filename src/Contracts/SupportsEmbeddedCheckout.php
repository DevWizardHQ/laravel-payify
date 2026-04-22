<?php

namespace DevWizard\Payify\Contracts;

use DevWizard\Payify\Dto\PaymentRequest;

interface SupportsEmbeddedCheckout
{
    public function embedScript(): string;

    /** @return array<string, string> */
    public function embedAttributes(PaymentRequest $request): array;
}

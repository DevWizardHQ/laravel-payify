<?php

namespace DevWizard\Payify\Contracts;

use DevWizard\Payify\Dto\PaymentRequest;

interface SupportsEmi
{
    /** @return array{enabled: bool, max_installments: int, supported_banks?: string[]} */
    public function emiOptions(): array;

    /** @return array<string, mixed>  Provider-specific EMI fields merged into payment payload */
    public function buildEmiPayload(PaymentRequest $request): array;
}

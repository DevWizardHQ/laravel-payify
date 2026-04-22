<?php

namespace DevWizard\Payify\Dto;

use DevWizard\Payify\Enums\TransactionStatus;

final readonly class PayoutResponse
{
    public function __construct(
        public string $transactionId,
        public ?string $providerPayoutId,
        public TransactionStatus $status,
        public float $amount,
        public string $currency,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
        public array $raw = [],
    ) {}
}

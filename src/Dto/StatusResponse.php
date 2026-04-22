<?php

namespace DevWizard\Payify\Dto;

use DevWizard\Payify\Enums\TransactionStatus;

final readonly class StatusResponse
{
    public function __construct(
        public string $transactionId,
        public TransactionStatus $status,
        public ?string $providerTransactionId = null,
        public ?float $paidAmount = null,
        public ?float $refundedAmount = null,
        public array $raw = [],
    ) {}
}

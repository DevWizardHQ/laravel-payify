<?php

namespace DevWizard\Payify\Dto;

final readonly class RefundRequest
{
    public function __construct(
        public string $transactionId,
        public ?float $amount = null,
        public ?string $reason = null,
        public array $extras = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            transactionId: $data['transactionId'] ?? $data['transaction_id'],
            amount: isset($data['amount']) ? (float) $data['amount'] : null,
            reason: $data['reason'] ?? null,
            extras: $data['extras'] ?? [],
        );
    }
}

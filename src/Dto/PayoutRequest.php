<?php

namespace DevWizard\Payify\Dto;

final readonly class PayoutRequest
{
    public function __construct(
        public string $reference,
        public float $amount,
        public string $currency,
        public string $receiverIdentifier,
        public ?string $receiverName = null,
        public ?string $reason = null,
        public array $extras = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            reference: (string) $data['reference'],
            amount: (float) $data['amount'],
            currency: (string) $data['currency'],
            receiverIdentifier: (string) ($data['receiverIdentifier'] ?? $data['receiver'] ?? ''),
            receiverName: $data['receiverName'] ?? $data['receiver_name'] ?? null,
            reason: $data['reason'] ?? null,
            extras: $data['extras'] ?? [],
        );
    }
}

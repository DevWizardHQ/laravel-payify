<?php

namespace DevWizard\Payify\Dto;

final readonly class WebhookPayload
{
    public function __construct(
        public string $provider,
        public string $event,
        public ?string $providerTransactionId,
        public ?string $reference,
        public ?float $amount,
        public ?string $currency,
        public array $raw,
        public bool $verified,
    ) {}
}

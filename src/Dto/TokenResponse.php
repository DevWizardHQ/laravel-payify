<?php

namespace DevWizard\Payify\Dto;

final readonly class TokenResponse
{
    public function __construct(
        public string $token,
        public ?string $last4 = null,
        public ?string $brand = null,
        public ?string $expiresAt = null,
        public array $raw = [],
    ) {}
}

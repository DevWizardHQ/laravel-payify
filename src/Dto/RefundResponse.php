<?php

namespace DevWizard\Payify\Dto;

use DevWizard\Payify\Enums\TransactionStatus;

final readonly class RefundResponse
{
    public function __construct(
        public string $transactionId,
        public string $refundId,
        public float $amount,
        public TransactionStatus $status,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
        public array $raw = [],
    ) {}

    public static function fromWebhook(WebhookPayload $payload, ?string $transactionId = null): self
    {
        return new self(
            transactionId: $transactionId ?? '',
            refundId: (string) ($payload->raw['refund_id'] ?? $payload->raw['refundId'] ?? ''),
            amount: (float) ($payload->amount ?? 0),
            status: TransactionStatus::Refunded,
            raw: $payload->raw,
        );
    }
}

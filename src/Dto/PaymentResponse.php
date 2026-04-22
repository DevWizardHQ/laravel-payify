<?php

namespace DevWizard\Payify\Dto;

use DevWizard\Payify\Enums\TransactionStatus;
use DevWizard\Payify\Models\Transaction;

final readonly class PaymentResponse
{
    public function __construct(
        public string $transactionId,
        public ?string $providerTransactionId,
        public TransactionStatus $status,
        public float $amount,
        public string $currency,
        public ?string $redirectUrl = null,
        public ?string $sessionId = null,
        public ?string $formHtml = null,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
        public array $raw = [],
    ) {}

    public static function fromTransaction(Transaction $txn): self
    {
        return new self(
            transactionId: $txn->id,
            providerTransactionId: $txn->provider_transaction_id,
            status: $txn->status,
            amount: (float) $txn->amount,
            currency: $txn->currency,
            errorCode: $txn->error_code,
            errorMessage: $txn->error_message,
            raw: $txn->response_payload ?? [],
        );
    }

    public function succeeded(): bool
    {
        return $this->status === TransactionStatus::Succeeded;
    }

    public function failed(): bool
    {
        return in_array($this->status, [TransactionStatus::Failed, TransactionStatus::Cancelled, TransactionStatus::Expired], true);
    }

    public function requiresRedirect(): bool
    {
        return $this->redirectUrl !== null;
    }
}

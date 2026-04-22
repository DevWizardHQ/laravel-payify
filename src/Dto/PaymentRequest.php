<?php

namespace DevWizard\Payify\Dto;

use Illuminate\Database\Eloquent\Model;

final readonly class PaymentRequest
{
    public function __construct(
        public float $amount,
        public string $currency,
        public string $reference,
        public ?Customer $customer = null,
        public ?string $callbackUrl = null,
        public ?string $webhookUrl = null,
        public ?string $mode = null,
        public ?Model $payable = null,
        public array $metadata = [],
        public array $extras = [],
    ) {}

    public static function fromArray(array $data): self
    {
        $customer = $data['customer'] ?? null;
        if (is_array($customer)) {
            $customer = Customer::fromArray($customer);
        }

        return new self(
            amount: (float) $data['amount'],
            currency: $data['currency'],
            reference: $data['reference'],
            customer: $customer,
            callbackUrl: $data['callback'] ?? $data['callbackUrl'] ?? null,
            webhookUrl: $data['webhook'] ?? $data['webhookUrl'] ?? null,
            mode: $data['mode'] ?? null,
            payable: $data['payable'] ?? null,
            metadata: $data['metadata'] ?? [],
            extras: $data['extras'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'reference' => $this->reference,
            'customer' => $this->customer?->toArray(),
            'callback_url' => $this->callbackUrl,
            'webhook_url' => $this->webhookUrl,
            'mode' => $this->mode,
            'payable_type' => $this->payable ? $this->payable::class : null,
            'payable_id' => $this->payable?->getKey(),
            'metadata' => $this->metadata,
            'extras' => $this->extras,
        ];
    }
}

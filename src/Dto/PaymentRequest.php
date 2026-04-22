<?php

namespace DevWizard\Payify\Dto;

use Illuminate\Database\Eloquent\Model;

final readonly class PaymentRequest
{
    /**
     * @param  array<int, LineItem>  $lineItems
     */
    public function __construct(
        public float $amount,
        public string $currency,
        public string $reference,
        public ?Customer $customer = null,
        public ?string $callbackUrl = null,
        public ?string $webhookUrl = null,
        public ?string $mode = null,
        public ?string $intent = null,
        public ?string $productCategory = null,
        public ?string $productName = null,
        public ?string $productProfile = null,
        public ?string $gateway = null,
        public ?string $emiOption = null,
        public ?int $emiMaxInstallments = null,
        public array $lineItems = [],
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

        $rawItems = $data['lineItems'] ?? $data['line_items'] ?? [];
        $lineItems = [];
        foreach ($rawItems as $item) {
            $lineItems[] = $item instanceof LineItem ? $item : LineItem::fromArray($item);
        }

        return new self(
            amount: (float) $data['amount'],
            currency: $data['currency'],
            reference: $data['reference'],
            customer: $customer,
            callbackUrl: $data['callback'] ?? $data['callbackUrl'] ?? null,
            webhookUrl: $data['webhook'] ?? $data['webhookUrl'] ?? null,
            mode: $data['mode'] ?? null,
            intent: $data['intent'] ?? null,
            productCategory: $data['productCategory'] ?? $data['product_category'] ?? null,
            productName: $data['productName'] ?? $data['product_name'] ?? null,
            productProfile: $data['productProfile'] ?? $data['product_profile'] ?? null,
            gateway: $data['gateway'] ?? null,
            emiOption: isset($data['emiOption']) ? (string) $data['emiOption'] : ($data['emi_option'] ?? null),
            emiMaxInstallments: isset($data['emiMaxInstallments']) ? (int) $data['emiMaxInstallments'] : (isset($data['emi_max_installments']) ? (int) $data['emi_max_installments'] : null),
            lineItems: $lineItems,
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
            'intent' => $this->intent,
            'product_category' => $this->productCategory,
            'product_name' => $this->productName,
            'product_profile' => $this->productProfile,
            'gateway' => $this->gateway,
            'emi_option' => $this->emiOption,
            'emi_max_installments' => $this->emiMaxInstallments,
            'line_items' => array_map(fn (LineItem $i) => $i->toArray(), $this->lineItems),
            'payable_type' => $this->payable ? $this->payable::class : null,
            'payable_id' => $this->payable?->getKey(),
            'metadata' => $this->metadata,
            'extras' => $this->extras,
        ];
    }
}

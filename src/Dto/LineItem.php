<?php

namespace DevWizard\Payify\Dto;

final readonly class LineItem
{
    public function __construct(
        public string $name,
        public float $price,
        public int $quantity = 1,
        public ?string $category = null,
        public array $metadata = [],
    ) {}

    public function total(): float
    {
        return $this->price * $this->quantity;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) $data['name'],
            price: (float) $data['price'],
            quantity: (int) ($data['quantity'] ?? 1),
            category: $data['category'] ?? null,
            metadata: $data['metadata'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'price' => $this->price,
            'quantity' => $this->quantity,
            'category' => $this->category,
            'metadata' => $this->metadata,
        ];
    }
}

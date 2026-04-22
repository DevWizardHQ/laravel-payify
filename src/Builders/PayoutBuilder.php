<?php

namespace DevWizard\Payify\Builders;

use DevWizard\Payify\Contracts\SupportsPayout;
use DevWizard\Payify\Dto\PayoutRequest;
use DevWizard\Payify\Dto\PayoutResponse;
use DevWizard\Payify\Support\ReferenceGenerator;

class PayoutBuilder
{
    private array $state = [];

    public function __construct(public readonly SupportsPayout $driver, array $state = [])
    {
        $this->state = $state;
    }

    public function amount(float $amount, ?string $currency = null): self
    {
        $this->state['amount'] = $amount;
        if ($currency) {
            $this->state['currency'] = $currency;
        }

        return $this;
    }

    public function reference(string $reference): self
    {
        $this->state['reference'] = $reference;

        return $this;
    }

    public function invoice(string $reference): self
    {
        return $this->reference($reference);
    }

    public function receiver(string $identifier, ?string $name = null): self
    {
        $this->state['receiverIdentifier'] = $identifier;
        if ($name !== null) {
            $this->state['receiverName'] = $name;
        }

        return $this;
    }

    public function reason(string $reason): self
    {
        $this->state['reason'] = $reason;

        return $this;
    }

    public function with(array $extras): self
    {
        $this->state['extras'] = array_merge($this->state['extras'] ?? [], $extras);

        return $this;
    }

    public function send(): PayoutResponse
    {
        return $this->driver->payout($this->buildRequest());
    }

    public function initiate(): PayoutResponse
    {
        return $this->driver->initPayout($this->buildRequest());
    }

    public function execute(string $providerPayoutId): PayoutResponse
    {
        return $this->driver->executePayout($providerPayoutId, $this->buildRequest());
    }

    private function buildRequest(): PayoutRequest
    {
        $data = $this->state;
        $data['currency'] ??= config('payify.default_currency', 'BDT');
        $data['reference'] ??= ReferenceGenerator::make('PO');

        return PayoutRequest::fromArray($data);
    }
}

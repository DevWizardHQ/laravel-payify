<?php

namespace DevWizard\Payify\Builders;

use DevWizard\Payify\Contracts\SupportsTokenization;
use DevWizard\Payify\Dto\Customer;
use DevWizard\Payify\Dto\PaymentRequest;
use DevWizard\Payify\Dto\PaymentResponse;
use DevWizard\Payify\Dto\TokenResponse;
use DevWizard\Payify\Support\ReferenceGenerator;
use Illuminate\Database\Eloquent\Model;

class AgreementBuilder
{
    /**
     * @param  array<string, mixed>  $state
     */
    public function __construct(
        public readonly SupportsTokenization $driver,
        private ?string $agreementId = null,
        private array $state = [],
    ) {}

    public function payerReference(string $msisdn): self
    {
        $this->state['payerReference'] = $msisdn;

        return $this;
    }

    public function callback(string $url): self
    {
        $this->state['callback'] = $url;

        return $this;
    }

    public function payable(Model $model): self
    {
        $this->state['payable'] = $model;

        return $this;
    }

    public function with(array $extras): self
    {
        $this->state['extras'] = array_merge($this->state['extras'] ?? [], $extras);

        return $this;
    }

    /** Create a brand-new agreement. Returns TokenResponse with redirectUrl. */
    public function create(): TokenResponse
    {
        $customer = new Customer(phone: $this->state['payerReference'] ?? null);

        return $this->driver->tokenize($customer);
    }

    /** Charge an existing active agreement. */
    public function charge(float $amount, ?string $reference = null, ?string $currency = null): PaymentResponse
    {
        if (! $this->agreementId) {
            throw new \InvalidArgumentException('agreementId required for charge(). Pass via ->agreement($id).');
        }

        return $this->driver->chargeToken($this->agreementId, new PaymentRequest(
            amount: $amount,
            currency: $currency ?? config('payify.default_currency', 'BDT'),
            reference: $reference ?? ReferenceGenerator::make(),
            extras: $this->state['extras'] ?? [],
        ));
    }

    public function cancel(?string $agreementId = null): bool
    {
        $id = $agreementId ?? $this->agreementId
            ?? throw new \InvalidArgumentException('agreementId required for cancel().');

        return $this->driver->detokenize($id);
    }
}
